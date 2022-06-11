<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use DateTime;
use DateTimeZone;
use Nette\Database\Drivers\MySqlDriver;
use Nette\Database\Explorer;
use Nette\Utils\Json;
use PDOException;
use RuntimeException;

class Technicolor implements RouterInterface
{

	/**
	 * @param string[] $serialNumberPrefixes
	 */
	public function __construct(
		private readonly Explorer $database,
		private readonly string $apiUrl,
		private readonly string $apiKey,
		private readonly array $serialNumberPrefixes,
		private readonly string $model,
	) {
	}


	/** @inheritDoc */
	public function getModelWithPrefixes(): array
	{
		return [$this->model => $this->serialNumberPrefixes];
	}


	/**
	 * Get keys, possibly from database.
	 *
	 * If the keys are not already in the database, store them.
	 *
	 * @param string $ssid
	 * @return array<int, WiFiKey>
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
	 * @return bool
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
	 * @return array<int, WiFiKey>
	 */
	private function generateKeys(string $ssid): array
	{
		$data = Json::decode($this->callApi(sprintf($this->apiUrl, $ssid, implode(',', $this->serialNumberPrefixes))));
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
				],
			],
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
	 * @return array<int, WiFiKey>
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
			$ssid,
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
			$ssid,
		);
		return (bool)$result;
	}


	/**
	 * Store keys to database.
	 *
	 * @param string $ssid
	 * @param array<int, WiFiKey> $keys
	 * @return bool false if no keys to store, true otherwise
	 */
	private function storeKeys(string $ssid, array $keys): bool
	{
		if (!$keys) {
			return false;
		}

		$datetime = new DateTime();
		$this->database->beginTransaction();
		try {
			/** @var DateTimeZone|false $timeZone */
			$timeZone = $datetime->getTimezone();
			$this->database->query(
				'INSERT INTO ssids',
				array(
					'ssid' => $ssid,
					'added' => $datetime,
					'added_timezone' => ($timeZone ? $timeZone->getName() : date_default_timezone_get()),
				),
			);
			$ssidId = $this->database->getInsertId();
			foreach ($keys as $key) {
				$this->database->query(
					'INSERT INTO `keys`',
					array(
						'key_ssid' => $ssidId,
						'serial' => $key->getSerial(),
						'key' => $key->getKey(),
						'type' => $key->getType()->value,
					),
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


	private function buildKey(string $serial, string $key, int $type): WiFiKey
	{
		preg_match('/^[a-z]+/i', $serial, $matches);
		$prefix = current($matches);
		if (!in_array($prefix, $this->serialNumberPrefixes)) {
			throw new RuntimeException('Unknown prefix for serial ' . $serial);
		}
		return new WiFiKey($serial, $prefix, null, null, $key, WiFiBand::from($type));
	}

}
