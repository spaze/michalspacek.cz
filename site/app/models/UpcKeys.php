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
	const SSID_UPC_PATTERN = '([Uu][Pp][Cc]).*';

	/** @var string */
	const SSID_VALID_PATTERN = '([Uu][Pp][Cc])[0-9]{7}';

	/** @var string */
	const SSID_PLACEHOLDER = 'UPC1234567';

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var string */
	protected $url;

	/** @var string */
	protected $apiKey;


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
	 * Get keys, possibly from database.
	 *
	 * If the keys are not already in the database, store them.
	 *
	 * @param string
	 * @return array (serial, key)
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
	 * @return array (serial, key)
	 */
	private function generateKeys($ssid)
	{
		$url = sprintf($this->url, $ssid);
		$data = file_get_contents($url, false, stream_context_create(['http' => ['header' => 'X-API-Key: ' . $this->apiKey]]));
		$data = \Nette\Utils\Json::decode($data);
		$keys = array();
		foreach (explode("\n", $data) as $line) {
			if (empty($line)) {
				continue;
			}

			list($serial, $key) = explode(',', $line);
			$keys[$serial] = $key;
		}
		return $keys;
	}


	/**
	 * Fetch keys from database.
	 *
	 * @param string
	 * @return array (serial, key)
	 */
	private function fetchKeys($ssid)
	{
		$rows = $this->database->fetchAll(
			'SELECT
				k.serial,
				k.key
			FROM
				`keys` k
				JOIN ssids s ON k.key_ssid = s.id_ssid
			WHERE s.ssid = ?
			ORDER BY k.id_key',
			$ssid
		);
		$result = array();
		foreach ($rows as $row) {
			$result[$row->serial] = $row->key;
		}
		return $result;
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
	 * @param array (serial, key)
	 */
	private function storeKeys($ssid, $keys)
	{
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
			foreach ($keys as $serial => $key) {
				$this->database->query(
					'INSERT INTO `keys`',
					array(
						'key_ssid' => $ssidId,
						'serial' => $serial,
						'key' => $key,
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
	}


	/**
	 * Return SSID validation pattern to detect whether the SSID is a UPC one.
	 *
	 * @return string
	 */
	public function getUpcSsidPattern()
	{
		return self::SSID_UPC_PATTERN;
	}


	/**
	 * Check whether the SSID is UPC SSID.
	 *
	 * @param string
	 * @return string
	 */
	public function isUpcSsid($ssid)
	{
		// Inspired by Nette\Forms\Validator::validatePattern()
		return (bool)\Nette\Utils\Strings::match($ssid, sprintf("\x01^(%s)\\z\x01u", self::SSID_UPC_PATTERN));
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


}
