<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use MichalSpacekCz\Pulse\Company;
use MichalSpacekCz\Pulse\Passwords\RatingGrade;

interface StorageSite
{

	public function getId(): string;


	public function getCompany(): Company;


	public function getStorageId(): string;


	public function getAlgorithm(string $id): StorageAlgorithm;


	public function hasAlgorithm(string $id): bool;


	public function addAlgorithm(StorageAlgorithm $algorithm): void;


	public function getLatestAlgorithm(): StorageAlgorithm;


	/**
	 * @return array<string, StorageAlgorithm>
	 */
	public function getHistoricalAlgorithms(): array;


	public function getRating(): RatingGrade;


	/**
	 * @return array<string, StorageAlgorithm>
	 */
	public function getAlgorithms(): array;


	public function isSecureStorage(): bool;


	public function getRecommendation(): ?string;

}
