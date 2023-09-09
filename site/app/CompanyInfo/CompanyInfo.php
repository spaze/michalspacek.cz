<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use MichalSpacekCz\CompanyInfo\Exceptions\CompanyInfoException;
use MichalSpacekCz\CompanyInfo\Exceptions\CompanyNotFoundException;
use MichalSpacekCz\CompanyInfo\Exceptions\UnsupportedCountryException;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Http\IResponse;
use Throwable;
use Tracy\Debugger;

class CompanyInfo
{

	private Cache $cache;


	/**
	 * @param array<int, CompanyRegister> $registers This is a list, support added in https://github.com/nette/di/pull/293
	 */
	public function __construct(
		private readonly array $registers,
		Storage $cacheStorage,
		private readonly bool $loadCompanyDataVisible = true,
	) {
		$this->cache = new Cache($cacheStorage, self::class);
	}


	public function getDetails(string $country, string $companyId): CompanyInfoDetails
	{
		try {
			$register = $this->getRegister($country);
			$cached = $this->cache->load("{$country}/{$companyId}", function (array|null &$dependencies) use ($companyId, $register): CompanyInfoDetails {
				$data = $register->getDetails($companyId);
				$dependencies[Cache::Expire] = $data->getStatus() === IResponse::S200_OK ? '3 days' : '15 minutes';
				return $data;
			});
			if (!$cached instanceof CompanyInfoDetails) {
				throw new CompanyInfoException(sprintf("Cached data for %s/%s is a '%s' not a '%s' object", $country, $companyId, get_debug_type($cached), CompanyInfoDetails::class));
			}
			return $cached;
		} catch (CompanyNotFoundException $e) {
			Debugger::log(sprintf("%s: %s, %s company id: %s", $e::class, $e->getMessage(), $country, $companyId));
			return new CompanyInfoDetails(IResponse::S400_BadRequest, 'Not found');
		} catch (UnsupportedCountryException) {
			return new CompanyInfoDetails(IResponse::S500_InternalServerError, 'Unsupported country');
		} catch (Throwable $e) {
			Debugger::log($e);
			return new CompanyInfoDetails(IResponse::S500_InternalServerError, 'Aww crap');
		}
	}


	/**
	 * @throws UnsupportedCountryException
	 */
	private function getRegister(string $country): CompanyRegister
	{
		foreach ($this->registers as $register) {
			if ($register->getCountry() === $country) {
				return $register;
			}
		}
		throw new UnsupportedCountryException();
	}


	public function isLoadCompanyDataVisible(): bool
	{
		return $this->loadCompanyDataVisible;
	}

}
