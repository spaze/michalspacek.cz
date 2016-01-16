<?php
namespace MichalSpacekCz;

/**
 * UPC Keys service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class UpcKeys
{

	/** @var string */
	const SSID_VALID_PATTERN = '([Uu][Pp][Cc])[0-9]{7}';

	/** @var string */
	const SSID_PLACEHOLDER = 'UPC1234567';

	/** @var integer */
	const SSID_TYPE_24GHZ = 1;

	/** @var integer */
	const SSID_TYPE_5GHZ = 2;

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var string */
	protected $url;

	/** @var string */
	protected $apiKey;

	/** @var array of strings */
	protected $prefixes;


	/**
	 * @param \Nette\Database\Context $context
	 */
	public function __construct(\Nette\Database\Context $context)
	{
		$this->database = $context;
	}


	/**
	 * Set URL used by API Gateway.
	 *
	 * @param string
	 */
	public function setUrl($url)
	{
		$this->url = $url;
	}


	/**
	 * Set API Key used by API Gateway.
	 *
	 * @param string
	 */
	public function setApiKey($apiKey)
	{
		$this->apiKey = $apiKey;
	}


	/**
	 * Set serial number prefixes to generate keys for.
	 *
	 * @param array of prefixes
	 */
	public function setPrefixes($prefixes)
	{
		$this->prefixes = $prefixes;
	}


	/**
	 * Get serial number prefixes to generate keys for.
	 *
	 * @return array of prefixes
	 */
	public function getPrefixes()
	{
		return $this->prefixes;
	}


	/**
	 * Get keys, possibly from database.
	 *
	 * If the keys are not already in the database, store them.
	 *
	 * @param string
	 * @return array of \stdClass (serial, key, type)
	 */
	public function getKeys($ssid)
	{
		$keys = $this->fetchKeys($ssid);
		if (!$keys) {
			$keys = $this->generateKeys($ssid);
			$this->storeKeys($ssid, $keys);
		}
		return $keys;
	}


	/**
	 * Save keys to a database if not already there.
	 *
	 * @param string
	 */
	public function saveKeys($ssid)
	{
		if (!$this->hasKeys($ssid)) {
			$this->storeKeys($ssid, $this->generateKeys($ssid));
		}
	}


	/**
	 * Get possible keys and serial for an SSID.
	 *
	 * @param string
	 * @return array of \stdClass (serial, key, type)
	 */
	private function generateKeys($ssid)
	{
		$url = sprintf($this->url, $ssid, implode(',', $this->prefixes));
		$data = file_get_contents($url, false, stream_context_create(['http' => ['header' => 'X-API-Key: ' . $this->apiKey]]));
		$data = \Nette\Utils\Json::decode($data);
		$keys = array();
		foreach (explode("\n", $data) as $line) {
			if (empty($line)) {
				continue;
			}

			list($serial, $key, $type) = explode(',', $line);
			$keys["{$type}-{$serial}"] = $this->buildKey($serial, $key, $type);
		}
		ksort($keys);
		return array_values($keys);
	}


	/**
	 * Fetch keys from database.
	 *
	 * @param string
	 * @return array of \stdClass (serial, key, type)
	 */
	private function fetchKeys($ssid)
	{
		$rows = $this->database->fetchAll(
			'SELECT
				k.serial,
				k.key,
				k.type
			FROM
				`keys` k
				JOIN ssids s ON k.key_ssid = s.id_ssid
			WHERE s.ssid = ?',
			$ssid
		);
		$result = array();
		foreach ($rows as $row) {
			$result["{$row->type}-{$row->serial}"] = $this->buildKey($row->serial, $row->key, $row->type);
		}
		ksort($result);
		return array_values($result);
	}


	/**
	 * Do we have keys for given SSID stored already?
	 *
	 * @param string
	 * @return boolean
	 */
	private function hasKeys($ssid)
	{
		$result = $this->database->fetchField(
			'SELECT
				COUNT(1)
			FROM
				`keys` k
				JOIN ssids s ON k.key_ssid = s.id_ssid
			WHERE s.ssid = ?',
			$ssid
		);
		return (bool)$result;
	}


	/**
	 * Store keys to database.
	 *
	 * @param string
	 * @param array of \stdClass (serial, key, type)
	 * @return boolean false if no keys to store, true otherwise
	 */
	private function storeKeys($ssid, array $keys)
	{
		if (!$keys) {
			return false;
		}

		$datetime = new \DateTime();
		$this->database->beginTransaction();
		try {
			$this->database->query(
				'INSERT INTO ssids',
				array(
					'ssid' => $ssid,
					'added' => $datetime,
					'added_timezone' => $datetime->getTimezone()->getName(),
				)
			);
			$ssidId = $this->database->getInsertId();
			foreach ($keys as $key) {
				$this->database->query(
					'INSERT INTO `keys`',
					array(
						'key_ssid' => $ssidId,
						'serial' => $key->serial,
						'key' => $key->key,
						'type' => $key->type,
					)
				);
			}
			$this->database->commit();
		} catch (\PDOException $e) {
			$this->database->rollBack();
			if ($e->getCode() != '23000' || $e->errorInfo[1] != \Nette\Database\Drivers\MySqlDriver::ERROR_DUPLICATE_ENTRY) {
				throw $e;
			}
		}

		return true;
	}


	/**
	 * Return SSID validation pattern to detect whether the SSID is a valid one.
	 *
	 * @return string
	 */
	public function getValidSsidPattern()
	{
		return self::SSID_VALID_PATTERN;
	}


	/**
	 * Check whether the SSID is valid for upc_keys to work.
	 *
	 * @param string
	 * @return string
	 */
	public function isValidSsid($ssid)
	{
		// Inspired by Nette\Forms\Validator::validatePattern()
		return (bool)\Nette\Utils\Strings::match($ssid, sprintf("\x01^(%s)\\z\x01u", self::SSID_VALID_PATTERN));
	}


	/**
	 * Return SSID placeholder.
	 *
	 * @return string
	 */
	public function getSsidPlaceholder()
	{
		return self::SSID_PLACEHOLDER;
	}


	/**
	 * Build key object.
	 *
	 * @param string
	 * @param string
	 * @param integer
	 * @return \stdClass
	 */
	private function buildKey($serial, $key, $type)
	{
		$result = new \stdClass();
		$result->serial = $serial;
		$result->key = $key;
		$result->type = $type;
		return $result;
	}


}
