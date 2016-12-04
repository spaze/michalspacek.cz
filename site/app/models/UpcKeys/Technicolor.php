<?php
namespace MichalSpacekCz\UpcKeys;

/**
 * UPC Technicolor keys service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Technicolor implements RouterInterface
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var string */
	protected $url;

	/** @var string */
	protected $apiKey;

	/** @var array of strings */
	protected $prefixes;

	/** @var string */
	protected $model;


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
	public function setPrefixes(array $prefixes)
	{
		$this->prefixes = $prefixes;
	}


	/**
	 * Set router model.
	 *
	 * @param string
	 */
	public function setModel($model)
	{
		$this->model = $model;
	}


	/**
	 * Get serial number prefixes to generate keys for.
	 *
	 * @return array of prefixes
	 */
	public function getModelWithPrefixes()
	{
		return [$this->model => $this->prefixes];
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
		try {
			$keys = $this->fetchKeys($ssid);
			if (!$keys) {
				$keys = $this->generateKeys($ssid);
				$this->storeKeys($ssid, $keys);
			}
			return $keys;
		} catch (\RuntimeException $e) {
			return [];
		}
	}


	/**
	 * Save keys to a database if not already there.
	 *
	 * @param string
	 * @return boolean
	 */
	public function saveKeys($ssid)
	{
		try {
			if (!$this->hasKeys($ssid)) {
				$this->storeKeys($ssid, $this->generateKeys($ssid));
			}
			return true;
		} catch (\RuntimeException $e) {
			return false;
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
		$data = \Nette\Utils\Json::decode($this->callApi(sprintf($this->url, $ssid, implode(',', $this->prefixes))));
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
	 * Request keys from API.
	 *
	 * @param string $url
	 * @return string
	 */
	private function callApi($url)
	{
		$context = stream_context_create();
		stream_context_set_params($context, [
			'notification' => function ($notificationCode, $severity, $message, $messageCode) {
				if ($notificationCode == STREAM_NOTIFY_FAILURE && $messageCode == 500) {
					throw new \RuntimeException(trim($message), $messageCode);
				}
			},
			'options' => [
				'http' => [
					'ignore_errors' => true,  // To supress PHP Warning: [...] HTTP/1.0 500 Internal Server Error
					'header' => 'X-API-Key: ' . $this->apiKey,
				]
			]
		]);
		return file_get_contents($url, false, $context);
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
		$result->oui = null;
		$result->mac = null;
		$result->key = $key;
		$result->type = $type;
		return $result;
	}


}
