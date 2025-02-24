<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use MichalSpacekCz\CompanyInfo\Exceptions\CompanyInfoException;
use MichalSpacekCz\CompanyInfo\Exceptions\CompanyNotFoundException;
use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Http\Client\HttpClientRequest;
use MichalSpacekCz\Http\Exceptions\HttpClientRequestException;
use Nette\Http\IResponse;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Override;
use stdClass;

/**
 * Register účtovných závierok service.
 *
 * See https://www.registeruz.sk/cruz-public/home/api for the docs.
 */
final readonly class CompanyRegisterRegisterUz implements CompanyRegister
{

	private const string DAY_ONE = '1993-01-01';

	private const string COUNTRY_CODE = 'sk';


	public function __construct(
		private Processor $schemaProcessor,
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
		try {
			/** @var Structure $expectArray */
			$expectArray = $this->schemaProcessor->process(
				Expect::type(Structure::class),
				Expect::array([
					Expect::int()->required(),
				]),
			);
			$schema = Expect::structure([
				'id' => $expectArray->otherItems()->required(),
			])->otherItems();
			/** @var object{id:array{0:int}} $data */
			$data = $this->schemaProcessor->process($schema, $units);
		} catch (ValidationException $e) {
			throw new CompanyInfoException($e->getMessage(), previous: $e);
		}
		$unit = $this->call('uctovna-jednotka', ['id' => $data->id[0]]);
		try {
			$schema = Expect::structure([
				'ico' => Expect::string()->required(),
				'dic' => Expect::string(),
				'nazovUJ' => Expect::string()->required(),
				'ulica' => Expect::string()->required(),
				'mesto' => Expect::string()->required(),
				'psc' => Expect::string()->required(),
			])->otherItems();
			/** @var object{ico:string, dic?:string, nazovUJ:string, ulica:string, mesto:string, psc:string} $data */
			$data = $this->schemaProcessor->process($schema, $unit);
		} catch (ValidationException $e) {
			throw new CompanyInfoException($e->getMessage(), previous: $e);
		}
		return new CompanyInfoDetails(
			IResponse::S200_OK,
			'OK',
			$data->ico,
			isset($data->dic) ? strtoupper(self::COUNTRY_CODE) . $data->dic : '',
			$data->nazovUJ,
			$data->ulica,
			$data->mesto,
			$data->psc,
			self::COUNTRY_CODE,
		);
	}


	/**
	 * @param string $method
	 * @param array<string, string|int> $parameters
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
