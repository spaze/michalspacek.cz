<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use DateTime;
use MichalSpacekCz\Pulse\Passwords\Algorithm;
use MichalSpacekCz\Pulse\Passwords\PasswordsSorting;
use MichalSpacekCz\Pulse\Passwords\Rating;
use MichalSpacekCz\Pulse\Passwords\SearchMatcher;
use MichalSpacekCz\Pulse\Passwords\Storage;
use MichalSpacekCz\Pulse\Passwords\StorageDisclosure;
use MichalSpacekCz\Pulse\Passwords\StorageRegistry;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;

class Passwords
{

	private Explorer $database;

	private Rating $rating;

	private Companies $companies;

	private Sites $sites;

	private PasswordsSorting $sorting;


	public function __construct(
		Explorer $context,
		Rating $rating,
		Companies $companies,
		Sites $sites,
		PasswordsSorting $sorting
	) {
		$this->database = $context;
		$this->rating = $rating;
		$this->companies = $companies;
		$this->sites = $sites;
		$this->sorting = $sorting;
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
		$storages = $this->processStorages($this->database->fetchAll($query, $orderBy), $sort);
		$searchMatcher = new SearchMatcher($search, $storages);
		foreach ($storages->getSites() as $site) {
			if (($rating && $site->getRating() !== $rating) || !$searchMatcher->match($site)) {
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

		return $this->processStorages($this->database->fetchAll($query, $sites), $this->sorting->getDefaultSort());
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

		return $this->processStorages($this->database->fetchAll($query, $companies), $this->sorting->getDefaultSort());
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

		return $this->processStorages($this->database->fetchAll($query, $companyId), $this->sorting->getDefaultSort());
	}


	/**
	 * @param Row[] $data
	 * @param string $sort
	 * @return StorageRegistry
	 */
	private function processStorages(array $data, string $sort): StorageRegistry
	{
		$registry = new StorageRegistry();
		foreach ($data as $row) {
			$siteId = $this->sites->generateId($row->siteId, $row->companyId);
			$storageKey = $this->sorting->isAnyCompanyAlphabetically($sort) ? (string)$row->companyId : $siteId;
			$algoKey = $row->algoId . '-' . ($row->from !== null ? $row->from->getTimestamp() : 'null');

			if (!$registry->hasCompany($row->companyId)) {
				$registry->addCompany(new Company($row->companyId, $row->companyName, $row->tradeName, $row->companyAlias, $row->sortName));
			}
			if (!$registry->hasSite($siteId)) {
				$registry->addSite(new Site($siteId, $row->siteId === null, $row->siteUrl, $row->siteAlias, $row->sharedWith ? Json::decode($row->sharedWith, Json::FORCE_ARRAY) : [], $registry->getCompany($row->companyId), $storageKey));
			}
			if (!$registry->hasStorage($storageKey)) {
				$registry->addStorage(new Storage($storageKey, $row->companyId));
			}
			if (!$registry->getStorage($storageKey)->hasSite($siteId)) {
				$registry->getStorage($storageKey)->addSite($registry->getSite($siteId));
			}
			if (!$registry->getStorage($storageKey)->getSite($siteId)->hasAlgorithm($algoKey)) {
				$algorithm = new Algorithm($algoKey, $row->algoName, $row->algoAlias, (bool)$row->algoSalted, (bool)$row->algoStretched, $row->from, (bool)$row->fromConfirmed, $row->attributes ? Json::decode($row->attributes) : null, $row->note);
				$registry->getStorage($storageKey)->getSite($siteId)->addAlgorithm($algorithm);
			}
			$disclosure = new StorageDisclosure($row->disclosureId, $row->disclosureUrl, $row->disclosureArchive, $row->disclosureNote, $row->disclosurePublished, $row->disclosureAdded, $row->disclosureType, $row->disclosureTypeAlias);
			$registry->getStorage($storageKey)->getSite($siteId)->getAlgorithm($algoKey)->addDisclosure($disclosure);
		}
		foreach ($registry->getSites() as $site) {
			$rating = $this->rating->get($site->getLatestAlgorithm());
			$site->setRating($rating, $this->rating->isSecureStorage($rating), $this->rating->getRecommendation($rating));
		}
		return $this->sorting->sort($registry, $sort);
	}


	/**
	 * Get slow hashes.
	 *
	 * @return array<string, string> of alias => name
	 */
	public function getSlowHashes(): array
	{
		return $this->database->fetchPairs(
			'SELECT alias, algo FROM password_algos WHERE alias IN (?) ORDER BY algo',
			$this->rating->getSlowHashes()
		);
	}


	/**
	 * Get disclosure types.
	 *
	 * @return Row[] of (id, alias, type)
	 */
	public function getDisclosureTypes(): array
	{
		return $this->database->fetchAll('SELECT id, alias, type FROM password_disclosure_types ORDER BY type');
	}


	/**
	 * Get visible disclosures.
	 *
	 * @return array<string, string> of alias => name
	 */
	public function getVisibleDisclosures(): array
	{
		return $this->database->fetchPairs(
			'SELECT alias, type FROM password_disclosure_types WHERE alias IN (?) ORDER BY type',
			$this->rating->getVisibleDisclosures()
		);
	}


	/**
	 * Get invisible disclosures.
	 *
	 * @return array<string, string> of alias => name
	 */
	public function getInvisibleDisclosures(): array
	{
		return $this->database->fetchPairs(
			'SELECT alias, type FROM password_disclosure_types WHERE alias IN (?) ORDER BY type',
			$this->rating->getInvisibleDisclosures()
		);
	}


	/**
	 * Get all algorithms.
	 *
	 * @return Row[] of id, algo, alias
	 */
	public function getAlgorithms(): array
	{
		return $this->database->fetchAll('SELECT id, algo, alias FROM password_algos ORDER BY algo');
	}


	/**
	 * @param string $name
	 * @return Row<mixed>|null
	 */
	public function getAlgorithmByName(string $name): ?Row
	{
		/** @var Row<mixed>|null $result */
		$result = $this->database->fetch('SELECT id, algo, alias, salted, stretched FROM password_algos WHERE algo = ?', $name);
		return $result;
	}


	/**
	 * Add algorithm.
	 *
	 * @param string $name
	 * @param string $alias
	 * @param bool $salted
	 * @param bool $stretched
	 * @return int Id of newly inserted algorithm
	 */
	private function addAlgorithm(string $name, string $alias, bool $salted, bool $stretched): int
	{
		$this->database->query('INSERT INTO password_algos', [
			'algo' => $name,
			'alias' => $alias,
			'salted' => $salted,
			'stretched' => $stretched,
		]);
		return (int)$this->database->getInsertId();
	}


	private function getDisclosureId(string $url, string $archive): ?int
	{
		return $this->database->fetchField('SELECT id FROM password_disclosures WHERE url = ? AND archive = ?', $url, $archive) ?: null;
	}


	/**
	 * Add disclosure.
	 *
	 * @param int $type
	 * @param string $url
	 * @param string $archive
	 * @param string $note
	 * @param string $published
	 * @return int Id of newly inserted disclosure
	 */
	private function addDisclosure(int $type, string $url, string $archive, string $note, string $published): int
	{
		$this->database->query('INSERT INTO password_disclosures', [
			'key_password_disclosure_types' => $type,
			'url' => $url,
			'archive' => $archive,
			'note' => (empty($note) ? null : $note),
			'published' => (empty($published) ? null : new DateTime($published)),
			'added' => new DateTime(),
		]);
		return (int)$this->database->getInsertId();
	}


	/**
	 * Get storage id by company id, algorithm id, site id.
	 *
	 * @param int $companyId
	 * @param int $algoId
	 * @param string $siteId
	 * @param string $from
	 * @param bool $fromConfirmed
	 * @param string|null $attributes
	 * @param string|null $note
	 * @return int|null
	 */
	private function getStorageId(int $companyId, int $algoId, string $siteId, string $from, bool $fromConfirmed, ?string $attributes, ?string $note): ?int
	{
		$result = $this->database->fetchField(
			'SELECT id FROM password_storages WHERE ?',
			array(
				'key_companies' => ($siteId === Sites::ALL ? $companyId : null),
				'key_password_algos' => $algoId,
				'key_sites' => ($siteId === Sites::ALL ? null : $siteId),
				'from' => (empty($from) ? null : new DateTime($from)),
				'from_confirmed' => $fromConfirmed,
				'attributes' => (empty($attributes) ? null : $attributes),
				'note' => (empty($note) ? null : $note),
			)
		);
		return $result ?: null;
	}


	/**
	 * Add password storage data.
	 *
	 * @param int $companyId
	 * @param int $algoId
	 * @param string $siteId
	 * @param string $from
	 * @param bool $fromConfirmed
	 * @param string $attributes
	 * @param string $note
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
			array(
				'key_password_disclosures' => $disclosureId,
				'key_password_storages' => $storageId,
			)
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
			? $values->site->id  // the value can also be "all"
			: $this->sites->add($newSite->url, $newSite->alias, $newSite->sharedWith, $companyId)
		);
		$algoId = (empty($newAlgo->algo) ? (int)$values->algo->id : $this->addAlgorithm($newAlgo->algo, $newAlgo->alias, $newAlgo->salted, $newAlgo->stretched));
		foreach ($values->disclosure->new as $disclosure) {
			if ($disclosure->url) {
				$disclosureId = $this->getDisclosureId($disclosure->url, $disclosure->archive);
				if (!$disclosureId) {
					$disclosureId = $this->addDisclosure($disclosure->disclosure, $disclosure->url, $disclosure->archive, $disclosure->note, $disclosure->published);
				}
				$storageId = $this->getStorageId($companyId, $algoId, $siteId, $values->algo->from, $values->algo->fromConfirmed, $values->algo->attributes, $values->algo->note);
				if (!$storageId) {
					$storageId = $this->addStorageData($companyId, $algoId, $siteId, $values->algo->from, $values->algo->fromConfirmed, $values->algo->attributes, $values->algo->note);
				}
				$this->pairDisclosureStorage($disclosureId, $storageId);
			}
		}
		$this->database->commit();
		return true;
	}

}
