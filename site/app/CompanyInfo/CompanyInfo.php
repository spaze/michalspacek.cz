<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use MichalSpacekCz\CompanyInfo\Exceptions\CompanyInfoException;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Http\IResponse;
use RuntimeException;
use Throwable;

class CompanyInfo
{

	private Cache $cache;


	public function __construct(
		private readonly Ares $ares,
		private readonly RegisterUz $registerUz,
		Storage $cacheStorage,
		private readonly bool $loadCompanyDataVisible = true,
	) {
		$this->cache = new Cache($cacheStorage, self::class);
	}


	/**
	 * @throws Throwable
	 * @throws CompanyInfoException
	 */
	public function getData(string $country, string $companyId): CompanyDetails
	{
		$cached = $this->cache->load("{$country}/{$companyId}", function (&$dependencies) use ($country, $companyId) {
			$data = match ($country) {
				'cz' => $this->ares->getDetails($companyId),
				'sk' => $this->registerUz->getDetails($companyId),
				default => throw new RuntimeException('Unsupported country'),
			};
			$dependencies[Cache::Expire] = ($data->status === IResponse::S200_OK ? '3 days' : '15 minutes');
			return $data;
		});
		if (!$cached instanceof CompanyDetails) {
			throw new CompanyInfoException(sprintf("Cached data for %s/%s is a '%s' not a '%s' object", $country, $companyId, get_debug_type($cached), CompanyDetails::class));
		}
		return $cached;
	}


	public function isLoadCompanyDataVisible(): bool
	{
		return $this->loadCompanyDataVisible;
	}

}
