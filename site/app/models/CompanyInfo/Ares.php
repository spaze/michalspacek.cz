<?php
namespace MichalSpacekCz\CompanyInfo;

/**
 * ARES service.
 *
 * See https://wwwinfo.mfcr.cz/ares/xml_doc/schemas/documentation/zkr_103.txt
 * for meaning of abbreviations like AA, NU, CD, CO etc. (in Czech)
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Ares implements CompanyDataInterface
{

	const STATUS_ERROR = 0;

	const STATUS_FOUND = 1;

	const STATUS_NOT_FOUND = 2;

	/** @var string */
	private $url;

	/** @var \MichalSpacekCz\KeyCdn */
	protected $keyCdn;


	/**
	 * @param \MichalSpacekCz\KeyCdn $keyCdn
	 */
	public function __construct(\MichalSpacekCz\KeyCdn $keyCdn)
	{
		$this->keyCdn = $keyCdn;
	}


	/**
	 * @param string $url
	 */
	public function setUrl($url)
	{
		$this->url = $url;
	}


	/**
	 * @param string $companyId
	 * @return array
	 */
	public function getData($companyId)
	{
		$company = new Data();
		try {
			if (empty($companyId)) {
				throw new \RuntimeException('Company Id is empty');
			}
			$content = $this->fetch($companyId);
			libxml_disable_entity_loader();
			$xml = simplexml_load_string($content);
			$ns = $xml->getDocNamespaces();
			$result = $xml->children($ns['are'])->children($ns['D'])->VH;
			$data = $xml->children($ns['are'])->children($ns['D'])->VBAS;
			if ((int)$result->K !== self::STATUS_FOUND) {
				throw new \UnexpectedValueException('Invalid status', (int)$result->K);
			}

			if (isset($data->AA)) {
				$street = (string)$data->AA->NU;
				$houseNumber = (string)$data->AA->CD;
				$streetNumber = (string)$data->AA->CO;
				$city = (string)$data->AA->N;
				$zip = (string)$data->AA->PSC;
				$country = strtolower($this->countryCode((string)$data->AA->KS));
			} else {
				$street = $houseNumber = $streetNumber = $city = $zip = $country = null;
			}

			$company->status = (int)$result->K;
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
		} catch (\UnexpectedValueException  $e) {
			\Tracy\Debugger::log(get_class($e) . ": {$e->getMessage()}, code: {$e->getCode()}, company id: {$companyId}");
			$company->status = self::STATUS_NOT_FOUND;
			$company->statusMessage = 'Not Found';
		} catch (\Exception $e) {
			\Tracy\Debugger::log($e);
			$company->status = self::STATUS_ERROR;
			$company->statusMessage = 'Error';
		}

		return $company;
	}


	/**
	 * Fetch data from API
	 *
	 * @param string $companyId
	 * @return string
	 */
	private function fetch($companyId)
	{
		$context = stream_context_create();
		stream_context_set_params($context, [
			'notification' => function ($notificationCode, $severity, $message, $messageCode) {
				if ($severity === STREAM_NOTIFY_SEVERITY_ERR) {
					throw new \RuntimeException(trim($message) . " ({$notificationCode})", $messageCode);
				}
			},
			'options' => [
				'http' => ['ignore_errors' => true],  // To supress PHP Warning: [...] HTTP/1.0 500 Internal Server Error
			],
		]);
		return file_get_contents($this->keyCdn->signUrl(sprintf($this->url, $companyId)), false, $context);
	}


	/**
	 * @param string $city
	 * @param string $street
	 * @param string $houseNumber
	 * @param string $streetNumber
	 * @return string
	 */
	private function formatStreet($city, $street, $houseNumber, $streetNumber)
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
	 * @param string $numericCode
	 * @return string ISO 3166-1 alpha-2 code
	 */
	private function countryCode($numericCode)
	{
		$codes = array(
			'203' => 'CZ',
			'703' => 'SK',
		);
		return (isset($codes[$numericCode]) ? $codes[$numericCode] : null);
	}

}
