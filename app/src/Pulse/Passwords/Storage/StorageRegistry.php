<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use MichalSpacekCz\Pulse\Company;

final class StorageRegistry
{

	/** @var array<int, Company> */
	private array $companies = [];

	/** @var array<string, StorageSite> */
	private array $sites = [];

	/** @var array<string, Storage> */
	private array $storages = [];


	/**
	 * @return array<int, Company>
	 */
	public function getCompanies(): array
	{
		return $this->companies;
	}


	public function getCompany(int $id): Company
	{
		return $this->companies[$id];
	}


	public function addCompany(Company $company): void
	{
		$this->companies[$company->getId()] = $company;
	}


	public function hasCompany(int $id): bool
	{
		return isset($this->companies[$id]);
	}


	/**
	 * @return array<string, StorageSite>
	 */
	public function getSites(): array
	{
		return $this->sites;
	}


	public function getSite(string $id): StorageSite
	{
		return $this->sites[$id];
	}


	public function addSite(StorageSite $site): void
	{
		$this->sites[$site->getId()] = $site;
	}


	public function hasSite(string $id): bool
	{
		return isset($this->sites[$id]);
	}


	/**
	 * @return array<string, Storage>
	 */
	public function getStorages(): array
	{
		return $this->storages;
	}


	public function getStorage(string $id): Storage
	{
		return $this->storages[$id];
	}


	public function addStorage(Storage $storage): void
	{
		$this->storages[$storage->getId()] = $storage;
	}


	public function hasStorage(string $id): bool
	{
		return isset($this->storages[$id]);
	}


	public function removeStorage(string $id): void
	{
		unset($this->storages[$id]);
	}


	public function removeStorageSite(StorageSite $site): void
	{
		$storage = $this->getStorage($site->getStorageId());
		$storage->removeSite($site);
		if (count($storage->getSites()) === 0) {
			$this->removeStorage($site->getStorageId());
		}
	}


	public function sortStorages(callable $callback): void
	{
		uasort($this->storages, $callback);
	}

}
