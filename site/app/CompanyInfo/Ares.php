<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use Exception;
use Nette\Http\IResponse;
use RuntimeException;
use SimpleXMLElement;
use Tracy\Debugger;
use UnexpectedValueException;

/**
 * ARES service.
 *
 * See https://wwwinfo.mfcr.cz/ares/xml_doc/schemas/documentation/zkr_103.txt
 * for meaning of abbreviations like AA, NU, CD, CO etc. (in Czech)
 */
class Ares implements CompanyDataInterface
{

	/**
	 * See kod_vyhledani in https://wwwinfo.mfcr.cz/ares/xml_doc/schemas/ares/ares_datatypes/v_1.0.5/ares_datatypes_v_1.0.5.xsd
	 */
	private const STATUS_FOUND = 1;


	public function __construct(
		private readonly string $url,
	) {
	}


	public function getData(string $companyId): Data
	{
		$company = new Data();
		try {
			if (empty($companyId)) {
				throw new RuntimeException('Company Id is empty');
			}
			$content = $this->fetch($companyId);
			$xml = simplexml_load_string($content);
			if (!$xml) {
				throw new RuntimeException("Can't parse XML received for company {$companyId}");
			}
			/** @var array<string, string> $ns */
			$ns = $xml->getDocNamespaces();
			$result = $xml->children($ns['are'])->children($ns['D'])->VH;
			$data = $xml->children($ns['are'])->children($ns['D'])->VBAS;
			if ((int)$result->K !== self::STATUS_FOUND) {
				throw new UnexpectedValueException('Invalid status', (int)$result->K);
			}

			if (isset($data->AA)) {
				$street = (string)$data->AA->NU;
				$houseNumber = (string)$data->AA->CD;
				$streetNumber = (string)$data->AA->CO;
				$city = (string)$data->AA->N;
				$zip = (string)$data->AA->PSC;
				$country = strtolower($this->countryCode($data));
			} else {
				$street = $houseNumber = $streetNumber = $city = $zip = $country = null;
			}

			$company->status = IResponse::S200_OK;
			$company->statusMessage = 'OK';
			$company->companyId = (string)$data->ICO;
			$company->companyTaxId = (string)$data->DIC;
			$company->company = (string)$data->OF;
			$company->street = $street;
			$company->houseNumber = $houseNumber;
			$company->streetNumber = $streetNumber;
			$company->streetFull = $this->formatStreet($city, $street, $houseNumber, $streetNumber);
			$company->city = $city;
			$company->zip = $zip;
			$company->country = $country;
		} catch (UnexpectedValueException $e) {
			Debugger::log(get_class($e) . ": {$e->getMessage()}, code: {$e->getCode()}, company id: {$companyId}");
			$company->status = IResponse::S400_BAD_REQUEST;
			$company->statusMessage = 'Not Found';
		} catch (RuntimeException $e) {
			$company->status = IResponse::S500_INTERNAL_SERVER_ERROR;
			$company->statusMessage = 'Error';
		} catch (Exception $e) {
			Debugger::log($e);
			$company->status = IResponse::S500_INTERNAL_SERVER_ERROR;
			$company->statusMessage = 'Error';
		}

		return $company;
	}


	private function fetch(string $companyId): string
	{
		$context = stream_context_create();
		stream_context_set_params($context, [
			'notification' => function ($notificationCode, $severity, $message, $messageCode) {
				if ($severity === STREAM_NOTIFY_SEVERITY_ERR) {
					throw new RuntimeException(trim($message) . " ({$notificationCode})", $messageCode);
				}
			},
			'options' => [
				'http' => ['ignore_errors' => true],  // To suppress PHP Warning: [...] HTTP/1.0 500 Internal Server Error
			],
		]);
		$url = sprintf($this->url, $companyId);
		$result = file_get_contents($url, false, $context);
		if (!$result) {
			throw new RuntimeException("Can't get result from {$url}");
		}
		return $result;
	}


	private function formatStreet(string $city, string $street, string $houseNumber, string $streetNumber): string
	{
		$result = $street;
		if (empty($result)) {
			$result = $city;
		}
		if (!empty($houseNumber) && !empty($streetNumber)) {
			$result .= " {$houseNumber}/{$streetNumber}";
		} elseif (!empty($houseNumber)) {
			$result .= " {$houseNumber}";
		} elseif (!empty($streetNumber)) {
			$result .= " {$streetNumber}";
		}
		return $result;
	}


	/**
	 * Return ISO 3166-1 alpha-2 by ISO 3166-1 numeric.
	 *
	 * @param SimpleXMLElement $data
	 * @return string
	 */
	private function countryCode(SimpleXMLElement $data): string
	{
		$codes = array(
			'203' => 'CZ',
			'703' => 'SK',
		);
		return ($codes[(string)$data->AA->KS] ?? substr((string)$data->DIC, 0, 2) ?: 'CZ');
	}

}
