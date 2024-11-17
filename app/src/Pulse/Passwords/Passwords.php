<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use DateTime;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Pulse\Companies;
use MichalSpacekCz\Pulse\Passwords\Algorithms\PasswordHashingAlgorithms;
use MichalSpacekCz\Pulse\Passwords\Disclosures\PasswordHashingDisclosures;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageRegistry;
use MichalSpacekCz\Pulse\Passwords\Storage\StorageRegistryFactory;
use MichalSpacekCz\Pulse\Sites;
use Nette\Database\Explorer;
use Nette\Utils\ArrayHash;

readonly class Passwords
{

	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
		private Companies $companies,
		private Sites $sites,
		private PasswordsSorting $sorting,
		private StorageRegistryFactory $storageRegistryFactory,
		private PasswordHashingAlgorithms $hashingAlgorithms,
		private PasswordHashingDisclosures $passwordHashingDisclosures,
	) {
	}


	public function getAllStorages(?string $rating, string $sort, ?string $search): StorageRegistry
	{
		$query = 'SELECT
				c.id AS companyId,
				c.name AS companyName,
				c.trade_name AS tradeName,
				COALESCE(c.trade_name, c.name) AS sortName,
				c.alias AS companyAlias,
				s.id AS siteId,
				s.url AS siteUrl,
				s.alias AS siteAlias,
				s.shared_with AS sharedWith,
				pa.id AS algoId,
				pa.alias AS algoAlias,
				pa.algo AS algoName,
				pa.salted AS algoSalted,
				pa.stretched AS algoStretched,
				ps.from,
				ps.from_confirmed AS fromConfirmed,
				pd.id AS disclosureId,
				pd.url AS disclosureUrl,
				pd.archive AS disclosureArchive,
				pd.note AS disclosureNote,
				pd.published AS disclosurePublished,
				pd.added AS disclosureAdded,
				pdt.alias AS disclosureTypeAlias,
				pdt.type AS disclosureType,
				ps.attributes,
				ps.note
			FROM companies c
				LEFT JOIN sites s ON s.key_companies = c.id
				JOIN password_storages ps ON ps.key_sites = s.id OR ps.key_companies = c.id
				JOIN password_algos pa ON pa.id = ps.key_password_algos
				JOIN password_disclosures_password_storages pdps ON pdps.key_password_storages = ps.id
				JOIN password_disclosures pd ON pdps.key_password_disclosures = pd.id
				JOIN password_disclosure_types pdt ON pdt.id = pd.key_password_disclosure_types
			ORDER BY ?';
		$orderBy = [
			'sortName' => !$this->sorting->isCompanyAlphabeticallyReversed($sort),
			's.url' => !$this->sorting->isCompanyAlphabeticallyReversed($sort),
			'ps.from' => false,
			'disclosurePublished' => true,
		];
		$storages = $this->storageRegistryFactory->get($this->typedDatabase->fetchAll($query, $orderBy), $sort);
		$searchMatcher = new SearchMatcher($search, $storages);
		foreach ($storages->getSites() as $site) {
			if (($rating !== null && $site->getRating()->name !== $rating) || !$searchMatcher->match($site)) {
				$storages->removeStorageSite($site);
			}
		}
		return $storages;
	}


	/**
	 * Get passwords storage data for specified sites.
	 *
	 * @param string[] $sites Aliases
	 * @return StorageRegistry
	 */
	public function getStoragesBySite(array $sites): StorageRegistry
	{
		$query = 'SELECT
				c.id AS companyId,
				c.name AS companyName,
				c.trade_name AS tradeName,
				COALESCE(c.trade_name, c.name) AS sortName,
				c.alias AS companyAlias,
				s.id AS siteId,
				s.url AS siteUrl,
				s.alias AS siteAlias,
				s.shared_with AS sharedWith,
				pa.id AS algoId,
				pa.alias AS algoAlias,
				pa.algo AS algoName,
				pa.salted AS algoSalted,
				pa.stretched AS algoStretched,
				ps.from,
				ps.from_confirmed AS fromConfirmed,
				pd.id AS disclosureId,
				pd.url AS disclosureUrl,
				pd.archive AS disclosureArchive,
				pd.note AS disclosureNote,
				pd.published AS disclosurePublished,
				pd.added AS disclosureAdded,
				pdt.alias AS disclosureTypeAlias,
				pdt.type AS disclosureType,
				ps.attributes,
				ps.note
			FROM companies c
				LEFT JOIN sites s ON s.key_companies = c.id
				JOIN password_storages ps ON ps.key_sites = s.id OR ps.key_companies = c.id
				JOIN password_algos pa ON pa.id = ps.key_password_algos
				JOIN password_disclosures_password_storages pdps ON pdps.key_password_storages = ps.id
				JOIN password_disclosures pd ON pdps.key_password_disclosures = pd.id
				JOIN password_disclosure_types pdt ON pdt.id = pd.key_password_disclosure_types
			WHERE s.alias IN (?)
			ORDER BY
				COALESCE(c.trade_name, c.name),
				s.url,
				ps.from DESC,
				pd.published';

		return $this->storageRegistryFactory->get($this->typedDatabase->fetchAll($query, $sites), $this->sorting->getDefaultSort());
	}


	/**
	 * Get passwords storage data for specified companies.
	 *
	 * @param string[] $companies Aliases
	 * @return StorageRegistry
	 */
	public function getStoragesByCompany(array $companies): StorageRegistry
	{
		$query = 'SELECT
				c.id AS companyId,
				c.name AS companyName,
				c.trade_name AS tradeName,
				COALESCE(c.trade_name, c.name) AS sortName,
				c.alias AS companyAlias,
				s.id AS siteId,
				s.url AS siteUrl,
				s.alias AS siteAlias,
				s.shared_with AS sharedWith,
				pa.id AS algoId,
				pa.alias AS algoAlias,
				pa.algo AS algoName,
				pa.salted AS algoSalted,
				pa.stretched AS algoStretched,
				ps.from,
				ps.from_confirmed AS fromConfirmed,
				pd.id AS disclosureId,
				pd.url AS disclosureUrl,
				pd.archive AS disclosureArchive,
				pd.note AS disclosureNote,
				pd.published AS disclosurePublished,
				pd.added AS disclosureAdded,
				pdt.alias AS disclosureTypeAlias,
				pdt.type AS disclosureType,
				ps.attributes,
				ps.note
			FROM companies c
				LEFT JOIN sites s ON s.key_companies = c.id
				JOIN password_storages ps ON ps.key_sites = s.id OR ps.key_companies = c.id
				JOIN password_algos pa ON pa.id = ps.key_password_algos
				JOIN password_disclosures_password_storages pdps ON pdps.key_password_storages = ps.id
				JOIN password_disclosures pd ON pdps.key_password_disclosures = pd.id
				JOIN password_disclosure_types pdt ON pdt.id = pd.key_password_disclosure_types
			WHERE c.alias IN (?)
			ORDER BY
				COALESCE(c.trade_name, c.name),
				s.url,
				ps.from DESC,
				pd.published';

		return $this->storageRegistryFactory->get($this->typedDatabase->fetchAll($query, $companies), $this->sorting->getDefaultSort());
	}


	public function getStoragesByCompanyId(int $companyId): StorageRegistry
	{
		$query = 'SELECT
				c.id AS companyId,
				c.name AS companyName,
				c.trade_name AS tradeName,
				COALESCE(c.trade_name, c.name) AS sortName,
				c.alias AS companyAlias,
				s.id AS siteId,
				s.url AS siteUrl,
				s.alias AS siteAlias,
				s.shared_with AS sharedWith,
				pa.id AS algoId,
				pa.alias AS algoAlias,
				pa.algo AS algoName,
				pa.salted AS algoSalted,
				pa.stretched AS algoStretched,
				ps.from,
				ps.from_confirmed AS fromConfirmed,
				pd.id AS disclosureId,
				pd.url AS disclosureUrl,
				pd.archive AS disclosureArchive,
				pd.note AS disclosureNote,
				pd.published AS disclosurePublished,
				pd.added AS disclosureAdded,
				pdt.alias AS disclosureTypeAlias,
				pdt.type AS disclosureType,
				ps.attributes,
				ps.note
			FROM companies c
				LEFT JOIN sites s ON s.key_companies = c.id
				JOIN password_storages ps ON ps.key_sites = s.id OR ps.key_companies = c.id
				JOIN password_algos pa ON pa.id = ps.key_password_algos
				JOIN password_disclosures_password_storages pdps ON pdps.key_password_storages = ps.id
				JOIN password_disclosures pd ON pdps.key_password_disclosures = pd.id
				JOIN password_disclosure_types pdt ON pdt.id = pd.key_password_disclosure_types
			WHERE c.id = ?
			ORDER BY
				COALESCE(c.trade_name, c.name),
				s.url,
				ps.from DESC,
				pd.published';

		return $this->storageRegistryFactory->get($this->typedDatabase->fetchAll($query, $companyId), $this->sorting->getDefaultSort());
	}


	/**
	 * Get storage id by company id, algorithm id, site id.
	 */
	private function getStorageId(int $companyId, int $algoId, string $siteId, string $from, bool $fromConfirmed, ?string $attributes, ?string $note): ?int
	{
		$result = $this->typedDatabase->fetchFieldIntNullable(
			'SELECT id FROM password_storages WHERE ?',
			[
				'key_companies' => ($siteId === Sites::ALL ? $companyId : null),
				'key_password_algos' => $algoId,
				'key_sites' => ($siteId === Sites::ALL ? null : $siteId),
				'from' => $from !== '' ? new DateTime($from) : null,
				'from_confirmed' => $fromConfirmed,
				'attributes' => $attributes !== null && $attributes !== '' ? $attributes : null,
				'note' => ($note !== null && $note !== '') ? $note : null,
			],
		);

		if ($result === null) {
			return null;
		}
		return $result;
	}


	/**
	 * Add password storage data.
	 *
	 * @return int Id of newly inserted storage
	 */
	private function addStorageData(int $companyId, int $algoId, string $siteId, string $from, bool $fromConfirmed, string $attributes, string $note): int
	{
		$this->database->query('INSERT INTO password_storages', [
			'key_companies' => ($siteId === Sites::ALL ? $companyId : null),
			'key_password_algos' => $algoId,
			'key_sites' => ($siteId === Sites::ALL ? null : (int)$siteId),
			'from' => (empty($from) ? null : new DateTime($from)),
			'from_confirmed' => $fromConfirmed,
			'attributes' => (empty($attributes) ? null : $attributes),
			'note' => (empty($note) ? null : $note),
		]);
		return (int)$this->database->getInsertId();
	}


	private function pairDisclosureStorage(int $disclosureId, int $storageId): void
	{
		$this->database->query(
			'INSERT INTO password_disclosures_password_storages',
			[
				'key_password_disclosures' => $disclosureId,
				'key_password_storages' => $storageId,
			],
		);
	}


	/**
	 * Add password storage.
	 *
	 * @param ArrayHash<int|string> $values
	 * @return bool True if storage added successfully
	 */
	public function addStorage(ArrayHash $values): bool
	{
		/** @var ArrayHash<int|string> $newCompany */
		$newCompany = $values->company->new;
		/** @var ArrayHash<int|string> $newSite */
		$newSite = $values->site->new;
		/** @var ArrayHash<int|string> $newAlgo */
		$newAlgo = $values->algo->new;

		$this->database->beginTransaction();
		$companyId = (empty($newCompany->name) ? (int)$values->company->id : $this->companies->add($newCompany->name, $newCompany->dba, $newCompany->alias));
		$siteId = (string)(empty($newSite->url)
			? $values->site->id // the value can also be "all"
			: $this->sites->add($newSite->url, $newSite->alias, $newSite->sharedWith, $companyId)
		);
		$algoId = (empty($newAlgo->algoName) ? (int)$values->algo->id : $this->hashingAlgorithms->addAlgorithm($newAlgo->algoName, $newAlgo->alias, $newAlgo->salted, $newAlgo->stretched));
		foreach ($values->disclosure->new as $disclosure) {
			if ($disclosure->url) {
				$disclosureId = $this->passwordHashingDisclosures->getDisclosureId($disclosure->url, $disclosure->archive);
				if ($disclosureId === null) {
					$disclosureId = $this->passwordHashingDisclosures->addDisclosure($disclosure->disclosureType, $disclosure->url, $disclosure->archive, $disclosure->note, $disclosure->published);
				}
				$storageId = $this->getStorageId($companyId, $algoId, $siteId, $values->algo->from, $values->algo->fromConfirmed, $values->algo->attributes, $values->algo->note);
				if ($storageId === null) {
					$storageId = $this->addStorageData($companyId, $algoId, $siteId, $values->algo->from, $values->algo->fromConfirmed, $values->algo->attributes, $values->algo->note);
				}
				$this->pairDisclosureStorage($disclosureId, $storageId);
			}
		}
		$this->database->commit();
		return true;
	}

}
