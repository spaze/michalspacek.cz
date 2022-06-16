<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use MichalSpacekCz\Pulse\Passwords\Algorithm;
use MichalSpacekCz\Pulse\Passwords\StorageSharedWith;

class Site
{

	/** @var array<int, StorageSharedWith> */
	private array $sharedWith = [];

	/** @var array<string, Algorithm> */
	private array $algorithms;

	private string $rating;

	private bool $secureStorage;

	private ?string $recommendation;


	/**
	 * @param array<int, array{url:string, alias:string}> $sharedWith
	 */
	public function __construct(
		private readonly string $id,
		private readonly bool $isTypeAll,
		private readonly string $url,
		private readonly string $alias,
		array $sharedWith,
		private readonly Company $company,
		private readonly string $storageId,
	) {
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
	 * @return array<int, StorageSharedWith>
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
