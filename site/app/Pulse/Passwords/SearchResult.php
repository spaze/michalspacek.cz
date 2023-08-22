<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use MichalSpacekCz\Pulse\Company;
use MichalSpacekCz\Pulse\Site;

class SearchResult
{

	/** @var array<int, Company> */
	private array $companyNames = [];

	/** @var array<int, Company> */
	private array $tradeNames = [];

	/** @var array<string, Algorithm> */
	private array $algorithmNames = [];

	/** @var array<string, Site> */
	private array $siteUrls = [];

	/** @var array<string, Site> */
	private array $siteAliases = [];

	/** @var array<int, StorageDisclosure> */
	private array $disclosureUrls = [];

	private bool $disclosureHistoryMatch = false;


	public function addCompanyNameMatch(Company $company): void
	{
		$this->companyNames[$company->getId()] = $company;
	}


	public function isCompanyNameMatch(Company $company): bool
	{
		return isset($this->companyNames[$company->getId()]);
	}


	public function addTradeNameMatch(Company $company): void
	{
		$this->tradeNames[$company->getId()] = $company;
	}


	public function isTradeNameMatch(Company $company): bool
	{
		return isset($this->tradeNames[$company->getId()]);
	}


	public function addAlgorithmNameMatch(Algorithm $algorithm): void
	{
		$this->algorithmNames[$algorithm->getId()] = $algorithm;
	}


	public function isAlgorithmNameMatch(Algorithm $algorithm): bool
	{
		return isset($this->algorithmNames[$algorithm->getId()]);
	}


	public function addSiteUrlMatch(Site $site): void
	{
		$this->siteUrls[$site->getId()] = $site;
	}


	public function isSiteUrlMatch(Site $site): bool
	{
		return isset($this->siteUrls[$site->getId()]);
	}


	public function addSiteAliasMatch(Site $site): void
	{
		$this->siteAliases[$site->getId()] = $site;
	}


	public function isSiteAliasMatch(Site $site): bool
	{
		return isset($this->siteAliases[$site->getId()]);
	}


	public function addDisclosureUrlMatch(StorageDisclosure $disclosure): void
	{
		$this->disclosureUrls[$disclosure->getId()] = $disclosure;
	}


	public function isDisclosureUrlMatch(StorageDisclosure $disclosure): bool
	{
		return isset($this->disclosureUrls[$disclosure->getId()]);
	}


	public function isAlgorithmDisclosureUrlMatch(Algorithm $algorithm): bool
	{
		foreach ($algorithm->getDisclosures() as $disclosure) {
			if ($this->isDisclosureUrlMatch($disclosure)) {
				return true;
			}
		}
		return false;
	}


	public function markDisclosureHistoryMatch(): void
	{
		$this->disclosureHistoryMatch = true;
	}


	public function isDisclosureHistoryMatch(): bool
	{
		return $this->disclosureHistoryMatch;
	}

}
