<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use Collator;
use MichalSpacekCz\Pulse\Passwords\Storage\Storage;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageRegistry;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageSite;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageSpecificSite;
use MichalSpacekCz\ShouldNotHappenException;

class PasswordsSorting
{

	private const string COMPANY_A_Z = 'a-z';
	private const string COMPANY_Z_A = 'z-a';
	private const string RATING_A_F = 'rating-a-f';
	private const string RATING_F_A = 'rating-f-a';
	private const string NEWEST_DISCLOSURES_FIRST = 'newest-disclosures-first';
	private const string NEWEST_DISCLOSURES_LAST = 'newest-disclosures-last';
	private const string NEWLY_ADDED_FIRST = 'newly-added-first';
	private const string NEWLY_ADDED_LAST = 'newly-added-last';

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

	private readonly Collator $collator;


	public function __construct()
	{
		$this->collator = new Collator('en_US');
	}


	public function sort(StorageRegistry $storages, string $sort): StorageRegistry
	{
		$sorter = match ($sort) {
			self::RATING_A_F, self::RATING_F_A => function (Storage $a, Storage $b) use ($storages, $sort): int {
				return $this->sortSites($storages, $a, $b, $sort, function (StorageRegistry $storages, StorageSite $siteA, StorageSite $siteB, string $sort): int {
					$result = $sort === self::RATING_A_F ? $siteA->getRating()->name <=> $siteB->getRating()->name : $siteB->getRating()->name <=> $siteA->getRating()->name;
					if ($result === 0) {
						$result = $this->collator->getSortKey($storages->getCompany($siteA->getCompany()->getId())->getSortName()) <=> $this->collator->getSortKey($storages->getCompany($siteB->getCompany()->getId())->getSortName());
						if ($result === 0) {
							$subKeyA = $siteA instanceof StorageSpecificSite ? $siteA->getUrl() : $siteA->getLatestAlgorithm()->getAlias();
							$subKeyB = $siteB instanceof StorageSpecificSite ? $siteB->getUrl() : $siteB->getLatestAlgorithm()->getAlias();
							$result = $subKeyA <=> $subKeyB;
						}
					}
					return $result;
				});
			},
			self::NEWEST_DISCLOSURES_FIRST, self::NEWEST_DISCLOSURES_LAST => function (Storage $a, Storage $b) use ($storages, $sort): int {
				return $this->sortSites($storages, $a, $b, $sort, function (StorageRegistry $storages, StorageSite $siteA, StorageSite $siteB, string $sort): int {
					return $sort === self::NEWEST_DISCLOSURES_LAST
						? $siteA->getLatestAlgorithm()->getLatestDisclosure()->getPublished() <=> $siteB->getLatestAlgorithm()->getLatestDisclosure()->getPublished()
						: $siteB->getLatestAlgorithm()->getLatestDisclosure()->getPublished() <=> $siteA->getLatestAlgorithm()->getLatestDisclosure()->getPublished();
				});
			},
			self::NEWLY_ADDED_FIRST, self::NEWLY_ADDED_LAST => function (Storage $a, Storage $b) use ($storages, $sort): int {
				return $this->sortSites($storages, $a, $b, $sort, function (StorageRegistry $storages, StorageSite $siteA, StorageSite $siteB, string $sort): int {
					$addedA = $siteA->getLatestAlgorithm()->getLatestDisclosure()->getAdded() ?? $siteA->getLatestAlgorithm()->getLatestDisclosure()->getPublished();
					$addedB = $siteB->getLatestAlgorithm()->getLatestDisclosure()->getAdded() ?? $siteB->getLatestAlgorithm()->getLatestDisclosure()->getPublished();
					return $sort === self::NEWLY_ADDED_LAST ? $addedA <=> $addedB : $addedB <=> $addedA;
				});
			},
			default => null,
		};
		if ($sorter !== null) {
			$storages->sortStorages($sorter);
		}
		return $storages;
	}


	/**
	 * @param callable(StorageRegistry, StorageSite, StorageSite, string): int $callback
	 */
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
