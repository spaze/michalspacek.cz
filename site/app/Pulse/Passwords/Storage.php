<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use MichalSpacekCz\Pulse\Site;

class Storage
{

	/** @var array<string, Site> */
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


	public function getSite(string $id): Site
	{
		return $this->sites[$id];
	}


	/**
	 * @return array<string, Site>
	 */
	public function getSites(): array
	{
		return $this->sites;
	}


	public function hasSite(string $id): bool
	{
		return isset($this->sites[$id]);
	}


	public function addSite(Site $site): void
	{
		$this->sites[$site->getId()] = $site;
	}


	public function removeSite(Site $site): void
	{
		unset($this->sites[$site->getId()]);
	}


	public function getSearchResult(): SearchResult
	{
		return $this->searchResult;
	}

}
