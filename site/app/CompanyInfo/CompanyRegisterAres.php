<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use MichalSpacekCz\CompanyInfo\Exceptions\CompanyInfoException;
use MichalSpacekCz\CompanyInfo\Exceptions\CompanyNotFoundException;
use Nette\Http\IResponse;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

/**
 * ARES service.
 *
 * See https://ares.gov.cz/stranky/vyvojar-info & https://ares.gov.cz/swagger-ui/ for the docs.
 * This is using the /ekonomicke-subjekty endpoint because it returns DIČ/tax id as well.
 */
class CompanyRegisterAres implements CompanyRegister
{

	public function __construct(
		private readonly Processor $schemaProcessor,
	) {
	}


	public function getCountry(): string
	{
		return 'cz';
	}


	/**
	 * @throws CompanyInfoException
	 * @throws CompanyNotFoundException
	 */
	public function getDetails(string $companyId): CompanyInfoDetails
	{
		if (empty($companyId)) {
			throw new CompanyInfoException('Company Id is empty');
		}
		$content = $this->fetch($companyId);
		try {
			$schema = Expect::structure([
				'ico' => Expect::string()->required(),
				'dic' => Expect::string(),
				'obchodniJmeno' => Expect::string()->required(),
				'sidlo' => Expect::structure([
					'nazevObce' => Expect::string(),
					'nazevUlice' => Expect::string(),
					'cisloDomovni' => Expect::int(),
					'cisloOrientacni' => Expect::int(),
					'cisloOrientacniPismeno' => Expect::string(),
					'psc' => Expect::int(),
					'kodStatu' => Expect::string(),
				])->otherItems(),
			])->otherItems();
			/** @var object{ico:string, dic:string, obchodniJmeno:string, sidlo:object{nazevObce:string, nazevUlice:string, cisloDomovni:int, cisloOrientacni:int, cisloOrientacniPismeno:string, psc:int, kodStatu:string}} $data */
			$data = $this->schemaProcessor->process($schema, Json::decode($content));
		} catch (JsonException | ValidationException $e) {
			throw new CompanyInfoException($e->getMessage(), previous: $e);
		}

		$streetAndNumber = $this->formatStreet(
			$data->sidlo->nazevObce,
			$data->sidlo->nazevUlice,
			$data->sidlo->cisloDomovni,
			$data->sidlo->cisloOrientacni,
			$data->sidlo->cisloOrientacniPismeno,
		);
		return new CompanyInfoDetails(
			IResponse::S200_OK,
			'OK',
			$data->ico,
			$data->dic ? $data->sidlo->kodStatu . $data->dic : '',
			$data->obchodniJmeno,
			$streetAndNumber,
			$data->sidlo->nazevObce,
			(string)$data->sidlo->psc,
			strtolower($data->sidlo->kodStatu),
		);
	}


	/**
	 * @throws CompanyInfoException
	 * @throws CompanyNotFoundException
	 */
	private function fetch(string $companyId): string
	{
		$url = "https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/{$companyId}";
		$context = stream_context_create();
		$setResult = stream_context_set_params($context, [
			'notification' => function (int $notificationCode, int $severity, ?string $message, int $messageCode) {
				if ($severity === STREAM_NOTIFY_SEVERITY_ERR) {
					throw new CompanyNotFoundException($messageCode !== IResponse::S404_NotFound ? $messageCode : null);
				}
			},
			'options' => [
				'http' => ['ignore_errors' => true], // To suppress PHP Warning: [...] HTTP/1.0 500 Internal Server Error
			],
		]);
		if (!$setResult) {
			throw new CompanyInfoException("Can't set stream context params to get contents from {$url}");
		}
		$result = file_get_contents($url, false, $context);
		if (!$result) {
			throw new CompanyInfoException("Can't get result from {$url}");
		}
		return $result;
	}


	private function formatStreet(?string $city, ?string $street, ?int $houseNumber, ?int $streetNumber, ?string $streetLetter): ?string
	{
		$result = $street;
		if (empty($result)) {
			$result = $city;
		}
		if (!empty($streetLetter)) {
			$streetNumber = ($streetNumber ?? '') . $streetLetter;
		}
		if (!empty($houseNumber) && !empty($streetNumber)) {
			$result = "{$result} {$houseNumber}/{$streetNumber}";
		} elseif (!empty($houseNumber)) {
			$result = "{$result} {$houseNumber}";
		} elseif (!empty($streetNumber)) {
			$result = "{$result} {$streetNumber}";
		}
		return $result;
	}

}
