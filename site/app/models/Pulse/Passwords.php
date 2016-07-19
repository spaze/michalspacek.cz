<?php
namespace MichalSpacekCz\Pulse;

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


	/**
	 * @param \Nette\Database\Context $context
	 */
	public function __construct(\Nette\Database\Context $context, Passwords\Rating $rating)
	{
		$this->database = $context;
		$this->rating = $rating;
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
	 * Get passwords storage data for a site.
	 *
	 * @param string $site
	 * @return \stdClass with companies, sites, algos, storages properties
	 */
	public function getStorages($site)
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
			WHERE s.alias = ?
			ORDER BY
				c.name,
				s.url,
				ps.from DESC,
				pd.published';

		return $this->processStorages($this->database->fetchAll($query, $site));
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

}
