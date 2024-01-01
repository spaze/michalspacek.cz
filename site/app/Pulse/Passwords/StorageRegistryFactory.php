<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use MichalSpacekCz\Pulse\Company;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageSpecificSite;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageWildcardSite;
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
		private AlgorithmAttributesFactory $algorithmAttributesFactory,
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
			$siteId = $this->sites->generateId($row->siteId, $row->companyId);
			$storageKey = $this->sorting->isAnyCompanyAlphabetically($sort) ? (string)$row->companyId : $siteId;
			$algoKey = $row->algoId . '-' . ($row->from !== null ? $row->from->getTimestamp() : 'null');

			if (!$registry->hasCompany($row->companyId)) {
				$registry->addCompany(new Company($row->companyId, $row->companyName, $row->tradeName, $row->companyAlias, $row->sortName));
			}
			$disclosure = new StorageDisclosure($row->disclosureId, $row->disclosureUrl, $row->disclosureArchive, $row->disclosureNote, $row->disclosurePublished, $row->disclosureAdded, $row->disclosureType, $row->disclosureTypeAlias);
			$algorithm = new Algorithm($algoKey, $row->algoName, $row->algoAlias, (bool)$row->algoSalted, (bool)$row->algoStretched, $row->from, (bool)$row->fromConfirmed, $this->algorithmAttributesFactory->get($row->attributes), $row->note, $disclosure);
			$addSite = !$registry->hasSite($siteId);
			if ($addSite) {
				if ($row->siteId === null) {
					$registry->addSite(new StorageWildcardSite($this->rating, $siteId, $registry->getCompany($row->companyId), $storageKey, $algorithm));
				} else {
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
