<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use DateTime;
use Nette\Database\Context;
use Nette\Database\Drivers\MySqlDriver;
use Nette\Utils\Json;
use PDOException;
use RuntimeException;
use stdClass;

class Technicolor implements RouterInterface
{

	/** @var Context */
	protected $database;

	/** @var string */
	protected $url;

	/** @var string */
	protected $apiKey;

	/** @var string[] */
	protected $prefixes;

	/** @var string */
	protected $model;


	public function __construct(Context $context)
	{
		$this->database = $context;
	}


	/**
	 * Set URL used by API Gateway.
	 *
	 * @param string $url
	 */
	public function setUrl(string $url): void
	{
		$this->url = $url;
	}


	/**
	 * Set API Key used by API Gateway.
	 *
	 * @param string $apiKey
	 */
	public function setApiKey(string $apiKey): void
	{
		$this->apiKey = $apiKey;
	}


	/**
	 * Set serial number prefixes to generate keys for.
	 *
	 * @param string[] $prefixes
	 */
	public function setPrefixes(array $prefixes): void
	{
		$this->prefixes = $prefixes;
	}


	/**
	 * Set router model.
	 *
	 * @param string $model
	 */
	public function setModel(string $model): void
	{
		$this->model = $model;
	}


	/**
	 * Get serial number prefixes to generate keys for.
	 *
	 * @return array<string, array<integer, string>>
	 */
	public function getModelWithPrefixes(): array
	{
		return [$this->model => $this->prefixes];
	}


	/**
	 * Get keys, possibly from database.
	 *
	 * If the keys are not already in the database, store them.
	 *
	 * @param string $ssid
	 * @return stdClass[] (serial, key, type)
	 */
	public function getKeys(string $ssid): array
	{
		try {
			$keys = $this->fetchKeys($ssid);
			if (!$keys) {
				$keys = $this->generateKeys($ssid);
				$this->storeKeys($ssid, $keys);
			}
			return $keys;
		} catch (RuntimeException $e) {
			return [];
		}
	}


	/**
	 * Save keys to a database if not already there.
	 *
	 * @param string $ssid
	 * @return boolean
	 */
	public function saveKeys(string $ssid): bool
	{
		try {
			if (!$this->hasKeys($ssid)) {
				$this->storeKeys($ssid, $this->generateKeys($ssid));
			}
			return true;
		} catch (RuntimeException $e) {
			return false;
		}
	}


	/**
	 * Get possible keys and serial for an SSID.
	 *
	 * @param string $ssid
	 * @return stdClass[] (serial, key, type)
	 */
	private function generateKeys(string $ssid): array
	{
		$data = Json::decode($this->callApi(sprintf($this->url, $ssid, implode(',', $this->prefixes))));
		$keys = array();
		foreach (explode("\n", $data) as $line) {
			if (empty($line)) {
				continue;
			}

			if (sscanf($line, '%20[^,],%20[^,],%d', $serial, $key, $type) != 3) {
				throw new RuntimeException('Incorrect number of tokens in ' . $line);
			}
			$keys["{$type}-{$serial}"] = $this->buildKey($serial, $key, $type);
		}
		ksort($keys);
		return array_values($keys);
	}


	private function callApi(string $url): string
	{
		$context = stream_context_create();
		stream_context_set_params($context, [
			'notification' => function ($notificationCode, $severity, $message, $messageCode) {
				if ($notificationCode == STREAM_NOTIFY_FAILURE && $messageCode >= 500) {
					throw new RuntimeException(trim($message), $messageCode);
				}
			},
			'options' => [
				'http' => [
					'ignore_errors' => true,  // To suppress PHP Warning: [...] HTTP/1.0 500 Internal Server Error
					'header' => 'X-API-Key: ' . $this->apiKey,
				]
			]
		]);
		$result = file_get_contents($url, false, $context);
		if (!$result) {
			throw new RuntimeException("Can't get result from {$url}");
		}
		return $result;
	}


	/**
	 * Fetch keys from database.
	 *
	 * @param string $ssid
	 * @return stdClass[] (serial, key, type)
	 */
	private function fetchKeys(string $ssid): array
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


	private function hasKeys(string $ssid): bool
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
	 * @param string $ssid
	 * @param stdClass[] $keys (serial, key, type)
	 * @return boolean false if no keys to store, true otherwise
	 */
	private function storeKeys(string $ssid, array $keys): bool
	{
		if (!$keys) {
			return false;
		}

		$datetime = new DateTime();
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
		} catch (PDOException $e) {
			$this->database->rollBack();
			if ($e->getCode() != '23000' || $e->errorInfo[1] != MySqlDriver::ERROR_DUPLICATE_ENTRY) {
				throw $e;
			}
		}

		return true;
	}


	private function buildKey(string $serial, string $key, int $type): stdClass
	{
		$result = new stdClass();
		$result->serial = $serial;
		$result->oui = null;
		$result->mac = null;
		$result->key = $key;
		$result->type = $type;
		return $result;
	}

}
