<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use MichalSpacekCz\Pulse\Passwords\Algorithm;
use MichalSpacekCz\Pulse\Passwords\Rating;
use MichalSpacekCz\Pulse\Passwords\RatingGrade;

class WildcardSite implements Site
{

	/** @var non-empty-array<string, Algorithm> */
	private array $algorithms;

	private ?RatingGrade $ratingGrade = null;


	public function __construct(
		private readonly Rating $rating,
		private readonly string $id,
		private readonly Company $company,
		private readonly string $storageId,
		Algorithm $algorithm,
	) {
		$this->algorithms = [$algorithm->getId() => $algorithm];
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


	/**
	 * @return array<string, Algorithm>
	 */
	public function getHistoricalAlgorithms(): array
	{
		return array_slice($this->algorithms, 1);
	}


	public function getRating(): RatingGrade
	{
		if (!$this->ratingGrade) {
			$this->ratingGrade = $this->rating->get($this->getLatestAlgorithm());
		}
		return $this->ratingGrade;
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
		return $this->rating->isSecureStorage($this->getRating());
	}


	public function getRecommendation(): ?string
	{
		return $this->rating->getRecommendation($this->getRating());
	}

}
