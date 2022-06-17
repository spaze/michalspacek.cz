<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use MichalSpacekCz\Pulse\Passwords\Algorithm;

class WildcardSite implements Site
{

	/** @var array<string, Algorithm> */
	private array $algorithms;

	private string $rating;

	private bool $secureStorage;

	private ?string $recommendation;


	public function __construct(
		private readonly string $id,
		private readonly Company $company,
		private readonly string $storageId,
	) {
	}


	public function getId(): string
	{
		return $this->id;
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
