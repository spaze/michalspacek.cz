<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use MichalSpacekCz\CompanyInfo\Exceptions\CompanyInfoException;
use MichalSpacekCz\CompanyInfo\Exceptions\CompanyNotFoundException;
use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Http\Client\HttpClientRequest;
use MichalSpacekCz\Http\Exceptions\HttpClientRequestException;
use Nette\Http\IResponse;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Override;
use stdClass;

/**
 * Register účtovných závierok service.
 *
 * See https://www.registeruz.sk/cruz-public/home/api for the docs.
 */
readonly class CompanyRegisterRegisterUz implements CompanyRegister
{

	private const DAY_ONE = '1993-01-01';

	private const COUNTRY_CODE = 'sk';


	public function __construct(
		private HttpClient $httpClient,
	) {
	}


	#[Override]
	public function getCountry(): string
	{
		return 'sk';
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
		$units = $this->call('uctovne-jednotky', ['zmenene-od' => self::DAY_ONE, 'ico' => $companyId]);
		if (empty($units->id)) {
			throw new CompanyNotFoundException();
		}
		$unit = $this->call('uctovna-jednotka', ['id' => reset($units->id)]);

		return new CompanyInfoDetails(
			IResponse::S200_OK,
			'OK',
			$unit->ico,
			(isset($unit->dic) ? strtoupper(self::COUNTRY_CODE) . $unit->dic : ''),
			$unit->nazovUJ,
			$unit->ulica,
			$unit->mesto,
			$unit->psc,
			self::COUNTRY_CODE,
		);
	}


	/**
	 * @param string $method
	 * @param array<string, string> $parameters
	 * @return stdClass JSON object
	 * @throws CompanyInfoException
	 */
	private function call(string $method, ?array $parameters = null): stdClass
	{
		if ($parameters !== null) {
			$query = '?' . http_build_query($parameters);
		} else {
			$query = '';
		}
		try {
			$content = $this->httpClient->get(new HttpClientRequest("https://www.registeruz.sk/cruz-public/api/{$method}{$query}"))->getBody();
		} catch (HttpClientRequestException $e) {
			throw new CompanyInfoException(code: IResponse::S500_InternalServerError, previous: $e);
		}
		try {
			$data = Json::decode($content);
		} catch (JsonException $e) {
			throw new CompanyInfoException($e->getMessage(), previous: $e);
		}
		if (!$data instanceof stdClass) {
			throw new CompanyInfoException(sprintf("Decoded JSON is a '%s' not a '%s' object (JSON: %s)", get_debug_type($data), stdClass::class, $content));
		}
		return $data;
	}

}
