<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use Collator;
use MichalSpacekCz\ShouldNotHappenException;

class PasswordsSorting
{

	private const COMPANY_A_Z = 'a-z';
	private const COMPANY_Z_A = 'z-a';
	private const RATING_A_F = 'rating-a-f';
	private const RATING_F_A = 'rating-f-a';

	/** @var array<string, string> */
	private array $sorting = [
		self::COMPANY_A_Z => 'company alphabetically',
		self::COMPANY_Z_A => 'company alphabetically reversed',
		self::RATING_A_F => 'best rating first',
		self::RATING_F_A => 'best rating last',
	];


	public function sort(StorageRegistry $storages, string $sort): StorageRegistry
	{
		if ($this->isAnyCompanyAlphabetically($sort)) {
			// done in the SQL query already
			return $storages;
		}

		switch ($sort) {
			case self::RATING_A_F:
			case self::RATING_F_A:
				$sorter = function (Storage $a, Storage $b) use ($storages, $sort): int {
					return $this->sortByRating($storages, $a, $b, $sort);
				};
				break;
		}
		if (isset($sorter)) {
			$storages->sortStorages($sorter);
		}
		return $storages;
	}


	private function sortByRating(StorageRegistry $storages, Storage $a, Storage $b, string $sort): int
	{
		if (count($a->getSites()) > 1 || count($b->getSites()) > 1) {
			throw new ShouldNotHappenException('When sorting by rating there should be just one site per disclosure');
		}
		$siteA = $storages->getSite((string)array_key_first($a->getSites()));
		$siteB = $storages->getSite((string)array_key_first($b->getSites()));
		$result = $sort === self::RATING_A_F ? $siteA->getRating() <=> $siteB->getRating() : $siteB->getRating() <=> $siteA->getRating();
		if ($result === 0) {
			static $collator;
			if (!$collator) {
				$collator = new Collator('en_US');
			}
			$result = $collator->getSortKey($storages->getCompany($a->getCompanyId())->getSortName()) <=> $collator->getSortKey($storages->getCompany($b->getCompanyId())->getSortName());
			if ($result === 0) {
				$result = $siteA->getUrl() <=> $siteB->getUrl();
			}
		}
		return $result;
	}


	/**
	 * @return array<string, string>
	 */
	public function getSorting(): array
	{
		return $this->sorting;
	}


	public function getDefaultSort(): string
	{
		return self::COMPANY_A_Z;
	}


	public function isCompanyAlphabetically(string $sort): bool
	{
		return $sort === self::COMPANY_A_Z;
	}


	public function isCompanyAlphabeticallyReversed(string $sort): bool
	{
		return $sort === self::COMPANY_Z_A;
	}


	public function isAnyCompanyAlphabetically(string $sort): bool
	{
		return $this->isCompanyAlphabetically($sort) || $this->isCompanyAlphabeticallyReversed($sort);
	}

}
