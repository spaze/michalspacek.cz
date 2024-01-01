<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use MichalSpacekCz\Pulse\Company;
use MichalSpacekCz\Pulse\Passwords\Algorithm;
use MichalSpacekCz\Pulse\Passwords\RatingGrade;

interface StorageSite
{

	public function getId(): string;


	public function getCompany(): Company;


	public function getStorageId(): string;


	public function getAlgorithm(string $id): Algorithm;


	public function hasAlgorithm(string $id): bool;


	public function addAlgorithm(Algorithm $algorithm): void;


	public function getLatestAlgorithm(): Algorithm;


	/**
	 * @return array<string, Algorithm>
	 */
	public function getHistoricalAlgorithms(): array;


	public function getRating(): RatingGrade;


	/**
	 * @return array<string, Algorithm>
	 */
	public function getAlgorithms(): array;


	public function isSecureStorage(): bool;


	public function getRecommendation(): ?string;

}
