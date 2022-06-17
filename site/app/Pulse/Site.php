<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use MichalSpacekCz\Pulse\Passwords\Algorithm;

interface Site
{

	public function getId(): string;


	public function getCompany(): Company;


	public function getStorageId(): string;


	public function getAlgorithm(string $id): Algorithm;


	public function hasAlgorithm(string $id): bool;


	public function addAlgorithm(Algorithm $algorithm): void;


	public function getLatestAlgorithm(): Algorithm;


	public function getRating(): string;


	public function setRating(string $rating, bool $secureStorage, ?string $recommendation): void;


	/**
	 * @return array<string, Algorithm>
	 */
	public function getAlgorithms(): array;


	public function isSecureStorage(): bool;


	public function getRecommendation(): ?string;

}
