<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use Nette\Http\IResponse;

/**
 * Register účtovných závierok service.
 *
 * See http://www.registeruz.sk/cruz-public/static/api.html for the docs.
 */
class RegisterUz implements CompanyDataInterface
{

	private const DAY_ONE = '1993-01-01';

	private const COUNTRY_CODE = 'sk';

	/** @var string */
	private $rootUrl;


	/**
	 * Root URL of the service, ends with a slash.
	 *
	 * @param string $rootUrl
	 */
	public function setRootUrl(string $rootUrl): void
	{
		if ($rootUrl[strlen($rootUrl) - 1] !== '/') {
			$rootUrl .= '/';
		}
		$this->rootUrl = $rootUrl;
	}


	public function getData(string $companyId): Data
	{
		$company = new Data();
		try {
			if (empty($companyId)) {
				throw new \RuntimeException('Company Id is empty');
			}
			$units = $this->call('uctovne-jednotky', ['zmenene-od' => self::DAY_ONE, 'ico' => $companyId]);
			if (!isset($units->id)) {
				throw new \UnexpectedValueException('Company not found');
			}
			$unit = $this->call('uctovna-jednotka', ['id' => reset($units->id)]);

			$company->status = IResponse::S200_OK;
			$company->statusMessage = 'OK';
			$company->companyId = $unit->ico;
			$company->companyTaxId = (isset($unit->dic) ? strtoupper(self::COUNTRY_CODE) . $unit->dic : '');
			$company->company = $unit->nazovUJ;
			$company->streetFull = $unit->ulica;
			$company->city = $unit->mesto;
			$company->zip = $unit->psc;
			$company->country = self::COUNTRY_CODE;
		} catch (\UnexpectedValueException $e) {
			\Tracy\Debugger::log(get_class($e) . ": {$e->getMessage()}, code: {$e->getCode()}, company id: {$companyId}");
			$company->status = IResponse::S400_BAD_REQUEST;
			$company->statusMessage = 'Not Found';
		} catch (\RuntimeException $e) {
			$company->status = IResponse::S500_INTERNAL_SERVER_ERROR;
			$company->statusMessage = 'Error';
		} catch (\Exception $e) {
			\Tracy\Debugger::log($e);
			$company->status = IResponse::S500_INTERNAL_SERVER_ERROR;
			$company->statusMessage = 'Error';
		}

		return $company;
	}


	/**
	 * @param string $method
	 * @param array $parameters
	 * @return \stdClass JSON object
	 */
	private function call(string $method, array $parameters = null): \stdClass
	{
		if ($parameters !== null) {
			$query = '?' . http_build_query($parameters);
		} else {
			$query = '';
		}
		$content = file_get_contents("{$this->rootUrl}{$method}{$query}");
		if (!$content) {
			throw new \RuntimeException(error_get_last()['message'], IResponse::S500_INTERNAL_SERVER_ERROR);
		}
		return \Nette\Utils\Json::decode($content);
	}

}
