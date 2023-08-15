<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use Collator;
use MichalSpacekCz\Pulse\Site;
use MichalSpacekCz\Pulse\SpecificSite;
use MichalSpacekCz\ShouldNotHappenException;

class PasswordsSorting
{

	private const COMPANY_A_Z = 'a-z';
	private const COMPANY_Z_A = 'z-a';
	private const RATING_A_F = 'rating-a-f';
	private const RATING_F_A = 'rating-f-a';
	private const NEWEST_DISCLOSURES_FIRST = 'newest-disclosures-first';
	private const NEWEST_DISCLOSURES_LAST = 'newest-disclosures-last';
	private const NEWLY_ADDED_FIRST = 'newly-added-first';
	private const NEWLY_ADDED_LAST = 'newly-added-last';

	/** @var array<string, string> */
	private array $sorting = [
		self::COMPANY_A_Z => 'company a-z',
		self::COMPANY_Z_A => 'company z-a',
		self::RATING_A_F => 'best rating first',
		self::RATING_F_A => 'best rating last',
		self::NEWEST_DISCLOSURES_FIRST => 'newest disclosures first',
		self::NEWEST_DISCLOSURES_LAST => 'newest disclosures last',
		self::NEWLY_ADDED_FIRST => 'newly added first',
		self::NEWLY_ADDED_LAST => 'newly added last',
	];


	public function sort(StorageRegistry $storages, string $sort): StorageRegistry
	{
		switch ($sort) {
			case self::RATING_A_F:
			case self::RATING_F_A:
				$sorter = function (Storage $a, Storage $b) use ($storages, $sort): int {
					return $this->sortSites($storages, $a, $b, $sort, function (StorageRegistry $storages, Site $siteA, Site $siteB, string $sort): int {
						$result = $sort === self::RATING_A_F ? $siteA->getRating()->name <=> $siteB->getRating()->name : $siteB->getRating()->name <=> $siteA->getRating()->name;
						if ($result === 0) {
							static $collator;
							if (!$collator) {
								$collator = new Collator('en_US');
							}
							$result = $collator->getSortKey($storages->getCompany($siteA->getCompany()->getId())->getSortName()) <=> $collator->getSortKey($storages->getCompany($siteB->getCompany()->getId())->getSortName());
							if ($result === 0) {
								$subKeyA = $siteA instanceof SpecificSite ? $siteA->getUrl() : $siteA->getLatestAlgorithm()->getAlias();
								$subKeyB = $siteB instanceof SpecificSite ? $siteB->getUrl() : $siteB->getLatestAlgorithm()->getAlias();
								$result = $subKeyA <=> $subKeyB;
							}
						}
						return $result;
					});
				};
				break;
			case self::NEWEST_DISCLOSURES_FIRST:
			case self::NEWEST_DISCLOSURES_LAST:
				$sorter = function (Storage $a, Storage $b) use ($storages, $sort): int {
					return $this->sortSites($storages, $a, $b, $sort, function (StorageRegistry $storages, Site $siteA, Site $siteB, string $sort): int {
						return $sort === self::NEWEST_DISCLOSURES_LAST
							? $siteA->getLatestAlgorithm()->getLatestDisclosure()->getPublished() <=> $siteB->getLatestAlgorithm()->getLatestDisclosure()->getPublished()
							: $siteB->getLatestAlgorithm()->getLatestDisclosure()->getPublished() <=> $siteA->getLatestAlgorithm()->getLatestDisclosure()->getPublished();
					});
				};
				break;
			case self::NEWLY_ADDED_FIRST:
			case self::NEWLY_ADDED_LAST:
				$sorter = function (Storage $a, Storage $b) use ($storages, $sort): int {
					return $this->sortSites($storages, $a, $b, $sort, function (StorageRegistry $storages, Site $siteA, Site $siteB, string $sort): int {
						$addedA = $siteA->getLatestAlgorithm()->getLatestDisclosure()->getAdded() ?? $siteA->getLatestAlgorithm()->getLatestDisclosure()->getPublished();
						$addedB = $siteB->getLatestAlgorithm()->getLatestDisclosure()->getAdded() ?? $siteB->getLatestAlgorithm()->getLatestDisclosure()->getPublished();
						return $sort === self::NEWLY_ADDED_LAST ? $addedA <=> $addedB : $addedB <=> $addedA;
					});
				};
				break;
		}
		if (isset($sorter)) {
			$storages->sortStorages($sorter);
		}
		return $storages;
	}


	private function sortSites(StorageRegistry $storages, Storage $a, Storage $b, string $sort, callable $callback): int
	{
		if (count($a->getSites()) > 1 || count($b->getSites()) > 1) {
			throw new ShouldNotHappenException('When sorting by rating there should be just one site per disclosure');
		}
		$siteA = $storages->getSite((string)array_key_first($a->getSites()));
		$siteB = $storages->getSite((string)array_key_first($b->getSites()));
		return $callback($storages, $siteA, $siteB, $sort);
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
