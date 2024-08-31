<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use DateTime;
use MichalSpacekCz\Pulse\Company;
use MichalSpacekCz\Pulse\Passwords\Algorithms\PasswordHashingAlgorithm;
use MichalSpacekCz\Pulse\Passwords\PasswordsSorting;
use MichalSpacekCz\Pulse\Passwords\Rating;
use MichalSpacekCz\Pulse\Sites;
use MichalSpacekCz\Utils\Exceptions\JsonItemNotStructureException;
use MichalSpacekCz\Utils\Exceptions\JsonItemsNotArrayException;
use Nette\Database\Row;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

readonly class StorageRegistryFactory
{

	public function __construct(
		private Rating $rating,
		private Sites $sites,
		private PasswordsSorting $sorting,
		private StorageAlgorithmAttributesFactory $algorithmAttributesFactory,
	) {
	}


	/**
	 * @param Row[] $data
	 * @param string $sort
	 * @return StorageRegistry
	 * @throws JsonException
	 * @throws JsonItemNotStructureException
	 * @throws JsonItemsNotArrayException
	 */
	public function get(array $data, string $sort): StorageRegistry
	{
		$registry = new StorageRegistry();
		foreach ($data as $row) {
			assert(is_int($row->companyId));
			assert(is_string($row->companyName));
			assert($row->tradeName === null || is_string($row->tradeName));
			assert(is_string($row->sortName));
			assert(is_string($row->companyAlias));
			assert($row->siteId === null || is_int($row->siteId));
			assert($row->siteUrl === null || is_string($row->siteUrl));
			assert($row->siteAlias === null || is_string($row->siteAlias));
			assert($row->sharedWith === null || is_string($row->sharedWith));
			assert(is_int($row->algoId));
			assert(is_string($row->algoAlias));
			assert(is_string($row->algoName));
			assert(is_int($row->algoSalted));
			assert(is_int($row->algoStretched));
			assert($row->from === null || $row->from instanceof DateTime);
			assert(is_int($row->fromConfirmed));
			assert(is_int($row->disclosureId));
			assert(is_string($row->disclosureUrl));
			assert(is_string($row->disclosureArchive));
			assert($row->disclosureNote === null || is_string($row->disclosureNote));
			assert($row->disclosurePublished === null || $row->disclosurePublished instanceof DateTime);
			assert($row->disclosureAdded === null || $row->disclosureAdded instanceof DateTime);
			assert(is_string($row->disclosureTypeAlias));
			assert(is_string($row->disclosureType));
			assert($row->attributes === null || is_string($row->attributes));
			assert($row->note === null || is_string($row->note));

			$siteId = $this->sites->generateId($row->siteId, $row->companyId);
			$storageKey = $this->sorting->isAnyCompanyAlphabetically($sort) ? (string)$row->companyId : $siteId;
			$hashingAlgorithm = new PasswordHashingAlgorithm($row->algoId, $row->algoName, $row->algoAlias, (bool)$row->algoSalted, (bool)$row->algoStretched);
			$algoKey = $hashingAlgorithm->getId() . '-' . ($row->from !== null ? $row->from->getTimestamp() : 'null');

			if (!$registry->hasCompany($row->companyId)) {
				$registry->addCompany(new Company($row->companyId, $row->companyName, $row->tradeName, $row->companyAlias, $row->sortName));
			}
			$disclosure = new StorageDisclosure($row->disclosureId, $row->disclosureUrl, $row->disclosureArchive, $row->disclosureNote, $row->disclosurePublished, $row->disclosureAdded, $row->disclosureType, $row->disclosureTypeAlias);
			$algorithm = new StorageAlgorithm($algoKey, $hashingAlgorithm, $row->from, (bool)$row->fromConfirmed, $this->algorithmAttributesFactory->get($row->attributes), $row->note, $disclosure);
			$addSite = !$registry->hasSite($siteId);
			if ($addSite) {
				if (isset($row->siteId, $row->siteUrl, $row->siteAlias)) {
					$registry->addSite(new StorageSpecificSite(
						$this->rating,
						$siteId,
						$row->siteUrl,
						$row->siteAlias,
						$row->sharedWith ? $this->getSharedWith($row->sharedWith) : [],
						$registry->getCompany($row->companyId),
						$storageKey,
						$algorithm,
					));
				} else {
					$registry->addSite(new StorageWildcardSite($this->rating, $siteId, $registry->getCompany($row->companyId), $storageKey, $algorithm));
				}
			}
			if (!$registry->hasStorage($storageKey)) {
				$registry->addStorage(new Storage($storageKey, $row->companyId));
			}
			if (!$registry->getStorage($storageKey)->hasSite($siteId)) {
				$registry->getStorage($storageKey)->addSite($registry->getSite($siteId));
			}
			if (!$registry->getStorage($storageKey)->getSite($siteId)->hasAlgorithm($algoKey)) {
				$registry->getStorage($storageKey)->getSite($siteId)->addAlgorithm($algorithm);
			} elseif (!$addSite) {
				$registry->getStorage($storageKey)->getSite($siteId)->getAlgorithm($algoKey)->addDisclosure($disclosure);
			}
		}
		return $this->sorting->sort($registry, $sort);
	}


	/**
	 * @return list<StorageSharedWith>
	 * @throws JsonException
	 * @throws JsonItemsNotArrayException
	 * @throws JsonItemNotStructureException
	 */
	private function getSharedWith(string $json): array
	{
		$sharedWith = [];
		$items = Json::decode($json, forceArrays: true);
		if (!is_array($items)) {
			throw new JsonItemsNotArrayException($items, $json);
		}
		foreach ($items as $item) {
			if (!is_array($item) || !isset($item['url']) || !isset($item['alias']) || !is_string($item['url']) || !is_string($item['alias'])) {
				throw new JsonItemNotStructureException($item, ['url', 'alias'], $json);
			}
			$sharedWith[] = new StorageSharedWith($item['url'], $item['alias']);
		}
		return $sharedWith;
	}

}
