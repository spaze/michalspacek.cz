<?php
namespace MichalSpacekCz\Pulse;

use \MichalSpacekCz\Pulse\Sites;

/**
 * Pulse passwords service.
 *
 * @author Michal Špaček
 * @package pulse.michalspacek.cz
 */
class Passwords
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \MichalSpacekCz\Pulse\Passwords\Rating */
	protected $rating;

	/** @var \MichalSpacekCz\Pulse\Companies */
	protected $companies;

	/** @var \MichalSpacekCz\Pulse\Sites */
	protected $sites;


	/**
	 * @param \Nette\Database\Context $context
	 * @param \MichalSpacekCz\Pulse\Passwords\Rating $rating
	 * @param \MichalSpacekCz\Pulse\Companies $companies
	 * @param \MichalSpacekCz\Pulse\Sites $sites
	 */
	public function __construct(
		\Nette\Database\Context $context,
		Passwords\Rating $rating,
		\MichalSpacekCz\Pulse\Companies $companies,
		Sites $sites
	)
	{
		$this->database = $context;
		$this->rating = $rating;
		$this->companies = $companies;
		$this->sites = $sites;
	}


	/**
	 * Get all passwords storage data.
	 *
	 * @return \stdClass with companies, sites, algos, storages properties
	 */
	public function getAllStorages()
	{
		$query = 'SELECT
				c.id AS companyId,
				c.name AS companyName,
				s.id AS siteId,
				s.url AS siteUrl,
				pa.id AS algoId,
				pa.alias AS algoAlias,
				pa.algo AS algoName,
				pa.salted AS algoSalted,
				pa.stretched AS algoStretched,
				ps.from,
				pd.url AS disclosureUrl,
				pd.archive AS disclosureArchive,
				pd.note AS disclosureNote,
				pdt.alias AS disclosureTypeAlias,
				pdt.type AS disclosureType,
				ps.attributes
			FROM companies c
				LEFT JOIN sites s ON s.key_companies = c.id
				JOIN password_storages ps ON ps.key_sites = s.id OR ps.key_companies = c.id
				JOIN password_algos pa ON pa.id = ps.key_password_algos
				JOIN password_disclosures_password_storages pdps ON pdps.key_password_storages = ps.id
				JOIN password_disclosures pd ON pdps.key_password_disclosures = pd.id
				JOIN password_disclosure_types pdt ON pdt.id = pd.key_password_disclosure_types
			ORDER BY
				c.name,
				s.url,
				ps.from DESC,
				pd.published';

		return $this->processStorages($this->database->fetchAll($query));
	}


	/**
	 * Get passwords storage data for specified sites.
	 *
	 * @param array $sites Aliases
	 * @return \stdClass with companies, sites, algos, storages properties
	 */
	public function getStorages(array $sites)
	{
		$query = 'SELECT
				c.id AS companyId,
				c.name AS companyName,
				s.id AS siteId,
				s.url AS siteUrl,
				pa.id AS algoId,
				pa.alias AS algoAlias,
				pa.algo AS algoName,
				pa.salted AS algoSalted,
				pa.stretched AS algoStretched,
				ps.from,
				pd.url AS disclosureUrl,
				pd.archive AS disclosureArchive,
				pd.note AS disclosureNote,
				pdt.alias AS disclosureTypeAlias,
				pdt.type AS disclosureType,
				ps.attributes
			FROM companies c
				LEFT JOIN sites s ON s.key_companies = c.id
				JOIN password_storages ps ON ps.key_sites = s.id OR ps.key_companies = c.id
				JOIN password_algos pa ON pa.id = ps.key_password_algos
				JOIN password_disclosures_password_storages pdps ON pdps.key_password_storages = ps.id
				JOIN password_disclosures pd ON pdps.key_password_disclosures = pd.id
				JOIN password_disclosure_types pdt ON pdt.id = pd.key_password_disclosure_types
			WHERE s.alias IN (?)
			ORDER BY
				c.name,
				s.url,
				ps.from DESC,
				pd.published';

		return $this->processStorages($this->database->fetchAll($query, $sites));
	}


	/**
	 * Get passwords storage data for a company.
	 *
	 * @param integer $companyId Company id
	 * @return \stdClass with companies, sites, algos, storages properties
	 */
	public function getStoragesByCompanyId($companyId)
	{
		$query = 'SELECT
				c.id AS companyId,
				c.name AS companyName,
				s.id AS siteId,
				s.url AS siteUrl,
				pa.id AS algoId,
				pa.alias AS algoAlias,
				pa.algo AS algoName,
				pa.salted AS algoSalted,
				pa.stretched AS algoStretched,
				ps.from,
				pd.url AS disclosureUrl,
				pd.archive AS disclosureArchive,
				pd.note AS disclosureNote,
				pdt.alias AS disclosureTypeAlias,
				pdt.type AS disclosureType,
				ps.attributes
			FROM companies c
				LEFT JOIN sites s ON s.key_companies = c.id
				JOIN password_storages ps ON ps.key_sites = s.id OR ps.key_companies = c.id
				JOIN password_algos pa ON pa.id = ps.key_password_algos
				JOIN password_disclosures_password_storages pdps ON pdps.key_password_storages = ps.id
				JOIN password_disclosures pd ON pdps.key_password_disclosures = pd.id
				JOIN password_disclosure_types pdt ON pdt.id = pd.key_password_disclosure_types
			WHERE c.id = ?
			ORDER BY
				c.name,
				s.url,
				ps.from DESC,
				pd.published';

		return $this->processStorages($this->database->fetchAll($query, $companyId));
	}


	/**
	 * Process passwords storage data.
	 *
	 * @param array $data
	 * @return \stdClass with companies, sites, algos, storages properties
	 */
	private function processStorages(array $data)
	{
		$storages = new \stdClass();
		foreach ($data as $row) {
			$storages->companies[$row->companyId] = $row->companyName;

			if (!isset($storages->sites[$row->siteId])) {
				$site = new \stdClass();
				$site->id = $row->siteId;
				$site->url = $row->siteUrl;
				$site->host = parse_url($row->siteUrl, PHP_URL_HOST);
				$site->companyId = $row->companyId;
				$storages->sites[$row->siteId] = $site;
			}

			$storages->algos[$row->algoId] = $row->algoName;
			$key = $row->algoId . '-' . ($row->from !== null ? $row->from->getTimestamp() : 'null');
			if (!isset($storages->storages[$row->companyId][$row->siteId][$key])) {
				$algo = new Passwords\Algorithm();
				$algo->id = $row->algoId;
				$algo->alias = $row->algoAlias;
				$algo->salted = $row->algoSalted;
				$algo->stretched = $row->algoStretched;
				$algo->from = $row->from;
				$attributes = \Nette\Utils\Json::decode($row->attributes);
				$algo->params = $attributes->params ?? null;
				$algo->fullAlgo = $this->formatFullAlgo($row->algoName, $attributes);
				$storages->storages[$row->companyId][$row->siteId][$key] = $algo;
			}
			$disclosure = new \stdClass();
			$disclosure->url = $row->disclosureUrl;
			$disclosure->archive = $row->disclosureArchive;
			$disclosure->note = $row->disclosureNote;
			$disclosure->type = $row->disclosureType;
			$storages->storages[$row->companyId][$row->siteId][$key]->disclosures[] = $disclosure;
			$storages->storages[$row->companyId][$row->siteId][$key]->disclosureTypes[$row->disclosureTypeAlias] = true;

		}
		foreach ($storages->sites as $site) {
			$site->rating = $this->rating->get(reset($storages->storages[$site->companyId][$site->id]));
		}
		return $storages;
	}


	/**
	 * Format full algo, if needed
	 *
	 * @param string $name main algo name
	 * @param \stdClass|null $attrs attributes
	 * @return string|null String of formatted algos, null if no inner or outer hashes used
	 */
	private function formatFullAlgo($name, \stdClass $attrs = null)
	{
		if (!isset($attrs->inner) && !isset($attrs->outer)) {
			return null;
		}

		$result = '';
		$count = 0;
		if (isset($attrs->outer)) {
			for ($i = count($attrs->outer) - 1; $i >= 0; $i--) {
				$result .= $attrs->outer[$i] . '(';
				$count++;
			}
		}
		$result .= $name . '(';
		$count++;
		if (isset($attrs->inner)) {
			for ($i = count($attrs->inner) - 1; $i >= 0; $i--) {
				$result .= $attrs->inner[$i] . '(';
				$count++;
			}
		}
		return $result . 'password' . str_repeat(')', $count);
	}


	/**
	 * Get slow hashes.
	 *
	 * @return array of alias => name
	 */
	public function getSlowHashes()
	{
		return $this->database->fetchPairs(
			'SELECT alias, algo FROM password_algos WHERE alias IN (?) ORDER BY algo',
			$this->rating->getSlowHashes()
		);
	}


	/**
	 * Get disclosure types.
	 *
	 * @return array of (id, alias, type)
	 */
	public function getDisclosureTypes()
	{
		return $this->database->fetchAll('SELECT id, alias, type FROM password_disclosure_types ORDER BY type');
	}


	/**
	 * Get visible disclosures.
	 *
	 * @return array of alias => name
	 */
	public function getVisibleDisclosures()
	{
		return $this->database->fetchPairs(
			'SELECT alias, type FROM password_disclosure_types WHERE alias IN (?) ORDER BY type',
			$this->rating->getVisibleDisclosures()
		);
	}


	/**
	 * Get invisible disclosures.
	 *
	 * @return array of alias => name
	 */
	public function getInvisibleDisclosures()
	{
		return $this->database->fetchPairs(
			'SELECT alias, type FROM password_disclosure_types WHERE alias IN (?) ORDER BY type',
			$this->rating->getInvisibleDisclosures()
		);
	}


	/**
	 * Get all algorithms.
	 *
	 * @return array of id, algo, alias
	 */
	public function getAlgorithms()
	{
		return $this->database->fetchAll('SELECT id, algo, alias FROM password_algos ORDER BY algo');
	}


	/**
	 * Get algorithm by name.
	 *
	 * @param string $name
	 * @return array of [id, algo, alias, salted, stretched]
	 */
	public function getAlgorithmByName($name)
	{
		return $this->database->fetch('SELECT id, algo, alias, salted, stretched FROM password_algos WHERE algo = ?', $name);
	}


	/**
	 * Add algorithm.
	 *
	 * @param string $name
	 * @param string $alias
	 * @param boolean $salted
	 * @param boolean $stretched
	 * @return integer Id of newly inserted algorithm
	 */
	private function addAlgorithm($name, $alias, $salted, $stretched)
	{
		$this->database->query('INSERT INTO password_algos', [
			'algo' => $name,
			'alias' => $alias,
			'salted' => $salted,
			'stretched' => $stretched,
		]);
		return $this->database->getInsertId();
	}


	/**
	 * Get disclosure id by URL.
	 *
	 * @param string $url
	 * @return integer id
	 */
	private function getDisclosureIdByUrl($url)
	{
		return $this->database->fetchField('SELECT id FROM password_disclosures WHERE url = ?', $url);
	}


	/**
	 * Add disclosure.
	 *
	 * @param integer $type
	 * @param string $url
	 * @param string $archive
	 * @param string $note
	 * @param string $published
	 * @return integer Id of newly inserted disclosure
	 */
	private function addDisclosure($type, $url, $archive, $note, $published)
	{
		$this->database->query('INSERT INTO password_disclosures', [
			'key_password_disclosure_types' => $type,
			'url' => $url,
			'archive' => $archive,
			'note' => (empty($note) ? null : $note),
			'published' => (empty($published) ? null : new \DateTime($published)),
		]);
		return $this->database->getInsertId();
	}


	/**
	 * Get storage id by company id, algorithm id, site id.
	 *
	 * @param integer $companyId
	 * @param integer $algoId
	 * @param integer $siteId
	 * @return array
	 */
	private function getStorageIdByCompanyIdAlgoIdSiteId($companyId, $algoId, $siteId)
	{
		return $this->database->fetchField(
			'SELECT id FROM password_storages WHERE ?',
			array(
				'key_companies' => ($siteId === Sites::ALL ? $companyId : null),
				'key_password_algos' => $algoId,
				'key_sites' => ($siteId === Sites::ALL ? null : $siteId),
			)
		);
	}


	/**
	 * Add password storage data.
	 *
	 * @param string $companyId
	 * @param string $algoId
	 * @param string $siteId
	 * @param string $from
	 * @param string $attributes
	 * @return integer Id of newly inserted storage
	 */
	private function addStorageData($companyId, $algoId, $siteId, $from, $attributes)
	{
		$this->database->query('INSERT INTO password_storages', [
			'key_companies' => ($siteId === Sites::ALL ? $companyId : null),
			'key_password_algos' => $algoId,
			'key_sites' => ($siteId === Sites::ALL ? null : $siteId),
			'from' => (empty($from) ? null : new \DateTime($from)),
			'attributes' => (empty($attributes) ? null : $attributes),
		]);
		return $this->database->getInsertId();
	}


	/**
	 * Pair disclosure with storage.
	 *
	 * @param integer $disclosureId
	 * @param integer $storageId
	 * @return null
	 */
	private function pairDisclosureStorage($disclosureId, $storageId)
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
	 * @param \Nette\Utils\ArrayHash $values
	 * @return boolean True if storage added successfully
	 */
	public function addStorage(\Nette\Utils\ArrayHash $values)
	{
		$this->database->beginTransaction();
		$companyId = (empty($values->company->new->name) ? (int)$values->company->id : $this->companies->add($values->company->new->name));
		$siteId = (empty($values->site->new->url)
			? (int)$values->site->id
			: $this->sites->add($values->site->new->url, $values->site->new->alias, $companyId)
		);
		$algoId = (empty($values->algo->new->algo)
			? $values->algo->id  // the value can also be "all"
			: $this->addAlgorithm($values->algo->new->algo, $values->algo->new->alias, $values->algo->new->salted, $values->algo->new->stretched)
		);
		foreach ($values->disclosure->new as $disclosure) {
			if ($disclosure->url) {
				$disclosureId = $this->getDisclosureIdByUrl($disclosure->url);
				if (!$disclosureId) {
					$disclosureId = $this->addDisclosure($disclosure->disclosure, $disclosure->url, $disclosure->archive, $disclosure->note, $disclosure->published);
				}
				$storageId = $this->getStorageIdByCompanyIdAlgoIdSiteId($companyId, $algoId, $siteId);
				if (!$storageId) {
					$storageId = $this->addStorageData($companyId, $algoId, $siteId, $values->algo->from, $values->algo->attributes);
				}
				$this->pairDisclosureStorage($disclosureId, $storageId);
			}
		}
		$this->database->commit();
		return true;
	}

}
