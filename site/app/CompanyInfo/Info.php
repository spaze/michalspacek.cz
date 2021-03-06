<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Http\IResponse;
use RuntimeException;

class Info
{

	private Ares $ares;

	private RegisterUz $registerUz;

	private Cache $cache;

	private bool $loadCompanyDataVisible = true;


	public function __construct(Ares $ares, RegisterUz $registerUz, Storage $cacheStorage)
	{
		$this->ares = $ares;
		$this->registerUz = $registerUz;
		$this->cache = new Cache($cacheStorage, self::class);
	}


	public function getData(string $country, string $companyId): Data
	{
		return $this->cache->load("{$country}/{$companyId}", function (&$dependencies) use ($country, $companyId) {
			switch ($country) {
				case 'cz':
					$data = $this->ares->getData($companyId);
					break;
				case 'sk':
					$data = $this->registerUz->getData($companyId);
					break;
				default:
					throw new RuntimeException('Unsupported country');
			}
			$dependencies[Cache::EXPIRATION] = ($data->status === IResponse::S200_OK ? '3 days' : '15 minutes');
			return $data;
		});
	}


	public function setLoadCompanyDataVisible(bool $visible): void
	{
		$this->loadCompanyDataVisible = $visible;
	}


	public function isLoadCompanyDataVisible(): bool
	{
		return $this->loadCompanyDataVisible;
	}

}
