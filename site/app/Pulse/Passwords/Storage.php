<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use MichalSpacekCz\Pulse\Site;

class Storage
{

	private string $id;

	private int $companyId;

	/** @var array<string, Site> */
	private array $sites = [];


	public function __construct(string $id, int $companyId)
	{
		$this->id = $id;
		$this->companyId = $companyId;
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

}
