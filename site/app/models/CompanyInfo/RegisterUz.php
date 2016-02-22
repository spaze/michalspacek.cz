<?php
namespace MichalSpacekCz\CompanyInfo;

/**
 * Register účtovných závierok service.
 *
 * See http://www.registeruz.sk/cruz-public/static/api.html for the docs.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class RegisterUz implements CompanyDataInterface
{

	const STATUS_ERROR = 0;

	const STATUS_FOUND = 1;

	const STATUS_NOT_FOUND = 2;

	const DAY_ONE = '1993-01-01';

	const COUNTRY_CODE = 'sk';

	/** @var string */
	private $rootUrl;


	/**
	 * Root URL of the service, ends with a slash.
	 *
	 * @param string $rootUrl
	 */
	public function setRootUrl($rootUrl)
	{
		if ($rootUrl[strlen($rootUrl) - 1] !== '/') {
			$rootUrl .= '/';
		}
		$this->rootUrl = $rootUrl;
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
			$units = $this->call('uctovne-jednotky', ['zmenene-od' => self::DAY_ONE, 'ico' => $companyId]);
			if (!isset($units->id)) {
				throw new \UnexpectedValueException('Company not found');
			}
			$unit = $this->call('uctovna-jednotka', ['id' => reset($units->id)]);

			$company->status = self::STATUS_FOUND;
			$company->statusMessage = 'OK';
			$company->companyId = $unit->ico;
			$company->companyTaxId = (isset($unit->dic) ? strtoupper(self::COUNTRY_CODE) . $unit->dic : '');
			$company->company = $unit->nazovUJ;
			$company->streetFull = $unit->ulica;
			$company->city = $unit->mesto;
			$company->zip = $unit->psc;
			$company->country = self::COUNTRY_CODE;
		} catch (\RuntimeException  $e) {
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
	 * @param string $method
	 * @param array $parameters
	 * @return \stdClass JSON object
	 */
	private function call($method, array $parameters = null)
	{
		if ($parameters !== null) {
			$query = '?' . http_build_query($parameters);
		} else {
			$query = '';
		}
		$content = file_get_contents("{$this->rootUrl}{$method}{$query}");
		if (!$content) {
			throw new \RuntimeException(error_get_last()['message'], self::STATUS_ERROR);
		}
		return \Nette\Utils\Json::decode($content);
	}

}
