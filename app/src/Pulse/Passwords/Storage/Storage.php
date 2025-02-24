<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use MichalSpacekCz\Pulse\Passwords\SearchResult;

final class Storage
{

	/** @var array<string, StorageSite> */
	private array $sites = [];

	private SearchResult $searchResult;


	public function __construct(
		private readonly string $id,
		private readonly int $companyId,
	) {
		$this->searchResult = new SearchResult();
	}


	public function getId(): string
	{
		return $this->id;
	}


	public function getCompanyId(): int
	{
		return $this->companyId;
	}


	public function getSite(string $id): StorageSite
	{
		return $this->sites[$id];
	}


	/**
	 * @return array<string, StorageSite>
	 */
	public function getSites(): array
	{
		return $this->sites;
	}


	public function hasSite(string $id): bool
	{
		return isset($this->sites[$id]);
	}


	public function addSite(StorageSite $site): void
	{
		$this->sites[$site->getId()] = $site;
	}


	public function removeSite(StorageSite $site): void
	{
		unset($this->sites[$site->getId()]);
	}


	public function getSearchResult(): SearchResult
	{
		return $this->searchResult;
	}

}
