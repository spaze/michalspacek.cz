<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use Exception;
use MichalSpacekCz\CompanyInfo\Exceptions\CompanyInfoException;
use Nette\Http\IResponse;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use RuntimeException;
use stdClass;
use Tracy\Debugger;
use UnexpectedValueException;

/**
 * Register účtovných závierok service.
 *
 * See http://www.registeruz.sk/cruz-public/static/api.html for the docs.
 */
class RegisterUz implements CompanyRegister
{

	private const DAY_ONE = '1993-01-01';

	private const COUNTRY_CODE = 'sk';

	private readonly string $rootUrl;


	public function __construct(string $rootUrl)
	{
		if ($rootUrl[strlen($rootUrl) - 1] !== '/') {
			$rootUrl .= '/';
		}
		$this->rootUrl = $rootUrl;
	}


	public function getDetails(string $companyId): CompanyDetails
	{
		$company = new CompanyDetails();
		try {
			if (empty($companyId)) {
				throw new RuntimeException('Company Id is empty');
			}
			$units = $this->call('uctovne-jednotky', ['zmenene-od' => self::DAY_ONE, 'ico' => $companyId]);
			if (!isset($units->id)) {
				throw new UnexpectedValueException('Company not found');
			}
			$unit = $this->call('uctovna-jednotka', ['id' => reset($units->id)]);

			$company->status = IResponse::S200_OK;
			$company->statusMessage = 'OK';
			$company->companyId = $unit->ico;
			$company->companyTaxId = (isset($unit->dic) ? strtoupper(self::COUNTRY_CODE) . $unit->dic : '');
			$company->company = $unit->nazovUJ;
			$company->streetAndNumber = $unit->ulica;
			$company->city = $unit->mesto;
			$company->zip = $unit->psc;
			$company->country = self::COUNTRY_CODE;
		} catch (UnexpectedValueException $e) {
			Debugger::log(get_class($e) . ": {$e->getMessage()}, code: {$e->getCode()}, company id: {$companyId}");
			$company->status = IResponse::S400_BadRequest;
			$company->statusMessage = 'Not Found';
		} catch (RuntimeException) {
			$company->status = IResponse::S500_InternalServerError;
			$company->statusMessage = 'Error';
		} catch (Exception $e) {
			Debugger::log($e);
			$company->status = IResponse::S500_InternalServerError;
			$company->statusMessage = 'Error';
		}

		return $company;
	}


	/**
	 * @param string $method
	 * @param array<string, string> $parameters
	 * @return stdClass JSON object
	 * @throws JsonException
	 * @throws CompanyInfoException
	 */
	private function call(string $method, ?array $parameters = null): stdClass
	{
		if ($parameters !== null) {
			$query = '?' . http_build_query($parameters);
		} else {
			$query = '';
		}
		$content = file_get_contents("{$this->rootUrl}{$method}{$query}");
		if (!$content) {
			$lastError = error_get_last();
			throw new RuntimeException($lastError ? $lastError['message'] : '', IResponse::S500_InternalServerError);
		}
		$data = Json::decode($content);
		if (!$data instanceof stdClass) {
			throw new CompanyInfoException(sprintf("Decoded JSON is a '%s' not a '%s' object (JSON: %s)", get_debug_type($data), stdClass::class, $content));
		}
		return $data;
	}

}
