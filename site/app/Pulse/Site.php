<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use MichalSpacekCz\Pulse\Passwords\Algorithm;
use MichalSpacekCz\Pulse\Passwords\StorageSharedWith;

class Site
{

	private string $id;

	private bool $isTypeAll;

	private ?string $url;

	private ?string $alias;

	/** @var array<integer, StorageSharedWith> */
	private array $sharedWith = [];

	private Company $company;

	private string $storageId;

	/** @var array<string, Algorithm> */
	private array $algorithms;

	private string $rating;

	private bool $secureStorage;

	private ?string $recommendation;


	/**
	 * Site constructor.
	 *
	 * @param string $id
	 * @param bool $isTypeAll
	 * @param string|null $url
	 * @param string|null $alias
	 * @param array<integer, array{url:string, alias:string}> $sharedWith
	 * @param Company $company
	 * @param string $storageId
	 */
	public function __construct(string $id, bool $isTypeAll, ?string $url, ?string $alias, array $sharedWith, Company $company, string $storageId)
	{
		$this->id = $id;
		$this->isTypeAll = $isTypeAll;
		$this->url = $url;
		$this->alias = $alias;
		$this->company = $company;
		$this->storageId = $storageId;
		foreach ($sharedWith as $sharedSite) {
			$this->sharedWith[] = new StorageSharedWith($sharedSite['url'], $sharedSite['alias']);
		}
	}


	public function getId(): string
	{
		return $this->id;
	}


	public function isTypeAll(): bool
	{
		return $this->isTypeAll;
	}


	public function getUrl(): string
	{
		return $this->url;
	}


	public function getAlias(): string
	{
		return $this->alias;
	}


	/**
	 * @return array<integer, StorageSharedWith>
	 */
	public function getSharedWith(): array
	{
		return $this->sharedWith;
	}


	public function getCompany(): Company
	{
		return $this->company;
	}


	public function getStorageId(): string
	{
		return $this->storageId;
	}


	public function getAlgorithm(string $id): Algorithm
	{
		return $this->algorithms[$id];
	}


	public function hasAlgorithm(string $id): bool
	{
		return isset($this->algorithms[$id]);
	}


	public function addAlgorithm(Algorithm $algorithm): void
	{
		$this->algorithms[$algorithm->getId()] = $algorithm;
	}


	public function getLatestAlgorithm(): Algorithm
	{
		return $this->algorithms[array_key_first($this->algorithms)];
	}


	public function getRating(): string
	{
		return $this->rating;
	}


	public function setRating(string $rating, bool $secureStorage, ?string $recommendation): void
	{
		$this->rating = $rating;
		$this->secureStorage = $secureStorage;
		$this->recommendation = $recommendation;
	}


	/**
	 * @return array<string, Algorithm>
	 */
	public function getAlgorithms(): array
	{
		return $this->algorithms;
	}


	public function isSecureStorage(): bool
	{
		return $this->secureStorage;
	}


	public function getRecommendation(): ?string
	{
		return $this->recommendation;
	}

}
