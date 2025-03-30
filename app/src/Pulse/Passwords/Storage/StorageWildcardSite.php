<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use MichalSpacekCz\Pulse\Company;
use MichalSpacekCz\Pulse\Passwords\Rating;
use MichalSpacekCz\Pulse\Passwords\RatingGrade;
use Override;

class StorageWildcardSite implements StorageSite
{

	/** @var non-empty-array<string, StorageAlgorithm> */
	private array $algorithms;

	private ?RatingGrade $ratingGrade = null;


	public function __construct(
		private readonly Rating $rating,
		private readonly string $id,
		private readonly Company $company,
		private readonly string $storageId,
		StorageAlgorithm $algorithm,
	) {
		$this->algorithms = [$algorithm->getId() => $algorithm];
	}


	#[Override]
	public function getId(): string
	{
		return $this->id;
	}


	#[Override]
	public function getCompany(): Company
	{
		return $this->company;
	}


	#[Override]
	public function getStorageId(): string
	{
		return $this->storageId;
	}


	#[Override]
	public function getAlgorithm(string $id): StorageAlgorithm
	{
		return $this->algorithms[$id];
	}


	#[Override]
	public function hasAlgorithm(string $id): bool
	{
		return isset($this->algorithms[$id]);
	}


	#[Override]
	public function addAlgorithm(StorageAlgorithm $algorithm): void
	{
		$this->algorithms[$algorithm->getId()] = $algorithm;
	}


	#[Override]
	public function getLatestAlgorithm(): StorageAlgorithm
	{
		return $this->algorithms[array_key_first($this->algorithms)];
	}


	/**
	 * @return array<string, StorageAlgorithm>
	 */
	#[Override]
	public function getHistoricalAlgorithms(): array
	{
		return array_slice($this->algorithms, 1);
	}


	#[Override]
	public function getRating(): RatingGrade
	{
		if ($this->ratingGrade === null) {
			$this->ratingGrade = $this->rating->get($this->getLatestAlgorithm());
		}
		return $this->ratingGrade;
	}


	/**
	 * @return array<string, StorageAlgorithm>
	 */
	#[Override]
	public function getAlgorithms(): array
	{
		return $this->algorithms;
	}


	#[Override]
	public function isSecureStorage(): bool
	{
		return $this->rating->isSecureStorage($this->getRating());
	}


	#[Override]
	public function getRecommendation(): ?string
	{
		return $this->rating->getRecommendation($this->getRating());
	}

}
