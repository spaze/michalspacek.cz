<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use MichalSpacekCz\CompanyInfo\Exceptions\CompanyInfoException;
use MichalSpacekCz\CompanyInfo\Exceptions\CompanyNotFoundException;
use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Http\Client\HttpClientRequest;
use MichalSpacekCz\Http\Exceptions\HttpClientRequestException;
use Nette\Http\IResponse;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Override;

/**
 * ARES service.
 *
 * See https://ares.gov.cz/stranky/vyvojar-info & https://ares.gov.cz/swagger-ui/ for the docs.
 * This is using the /ekonomicke-subjekty endpoint because it returns DIČ/tax id as well.
 */
readonly class CompanyRegisterAres implements CompanyRegister
{

	public function __construct(
		private Processor $schemaProcessor,
		private HttpClient $httpClient,
	) {
	}


	#[Override]
	public function getCountry(): string
	{
		return 'cz';
	}


	/**
	 * @throws CompanyInfoException
	 * @throws CompanyNotFoundException
	 */
	#[Override]
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
			/** @var object{ico:string, dic:string|null, obchodniJmeno:string, sidlo:object{nazevObce:string, nazevUlice:string, cisloDomovni:int, cisloOrientacni:int, cisloOrientacniPismeno:string, psc:int, kodStatu:string}} $data */
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
			$data->dic ?? '',
			$data->obchodniJmeno,
			$streetAndNumber,
			$data->sidlo->nazevObce,
			(string)$data->sidlo->psc,
			strtolower($data->sidlo->kodStatu),
		);
	}


	/**
	 * @throws CompanyNotFoundException
	 */
	private function fetch(string $companyId): string
	{
		$url = "https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/{$companyId}";
		try {
			return $this->httpClient->get(new HttpClientRequest($url))->getBody();
		} catch (HttpClientRequestException $e) {
			throw new CompanyNotFoundException($e->getCode() !== IResponse::S404_NotFound ? $e->getCode() : null, $e);
		}
	}


	private function formatStreet(?string $city, ?string $street, ?int $houseNumber, ?int $streetNumber, ?string $streetLetter): ?string
	{
		$result = $street !== null && $street !== '' ? $street : $city;
		if ($streetLetter !== null && $streetLetter !== '') {
			$streetNumber = ($streetNumber ?? '') . $streetLetter;
		}
		$hasHouseNumber = $houseNumber !== null && $houseNumber !== 0;
		$hasStreetNumber = $streetNumber !== null && $streetNumber !== 0;
		if ($hasHouseNumber && $hasStreetNumber) {
			$result = "{$result} {$houseNumber}/{$streetNumber}";
		} elseif ($hasHouseNumber) {
			$result = "{$result} {$houseNumber}";
		} elseif ($hasStreetNumber) {
			$result = "{$result} {$streetNumber}";
		}
		return $result;
	}

}
