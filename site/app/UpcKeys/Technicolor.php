<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use DateTime;
use MichalSpacekCz\UpcKeys\Exceptions\UpcKeysApiResponseInvalidException;
use Nette\Database\Explorer;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
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


	public function getModelWithPrefixes(): array
	{
		return [$this->model => $this->serialNumberPrefixes];
	}


	/**
	 * Get keys, possibly from database.
	 *
	 * If the keys are not already in the database, store them.
	 *
	 * @return array<int, WiFiKey>
	 * @throws UpcKeysApiResponseInvalidException
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
		} catch (RuntimeException) {
			return [];
		}
	}


	/**
	 * Save keys to a database if not already there.
	 *
	 * @throws UpcKeysApiResponseInvalidException
	 */
	public function saveKeys(string $ssid): bool
	{
		try {
			if (!$this->hasKeys($ssid)) {
				$this->storeKeys($ssid, $this->generateKeys($ssid));
			}
			return true;
		} catch (RuntimeException) {
			return false;
		}
	}


	/**
	 * Get possible keys and serial for an SSID.
	 *
	 * @param string $ssid
	 * @return array<int, WiFiKey>
	 * @throws UpcKeysApiResponseInvalidException
	 */
	private function generateKeys(string $ssid): array
	{
		$json = $this->callApi(sprintf($this->apiUrl, $ssid, implode(',', $this->serialNumberPrefixes)));
		try {
			$data = Json::decode($json);
		} catch (JsonException $e) {
			throw new UpcKeysApiResponseInvalidException(previous: $e);
		}
		if (!is_string($data)) {
			throw new UpcKeysApiResponseInvalidException($json);
		}
		$keys = [];
		foreach (explode("\n", $data) as $line) {
			if (empty($line)) {
				continue;
			}

			if (!preg_match('/([^,]+),([^,]+),(\d+)/', $line, $matches)) {
				throw new RuntimeException('Incorrect number of tokens in ' . $line);
			}
			[, $serial, $key, $type] = $matches;
			$keys["{$type}-{$serial}"] = $this->buildKey($serial, $key, (int)$type);
		}
		ksort($keys);
		return array_values($keys);
	}


	private function callApi(string $url): string
	{
		$context = stream_context_create();
		$setResult = stream_context_set_params($context, [
			'notification' => function (int $notificationCode, int $severity, ?string $message, int $messageCode) {
				if ($notificationCode == STREAM_NOTIFY_FAILURE && $messageCode >= 500) {
					throw new RuntimeException($message ? trim($message) : '', $messageCode);
				}
			},
			'options' => [
				'http' => [
					'ignore_errors' => true, // To suppress PHP Warning: [...] HTTP/1.0 500 Internal Server Error
					'header' => 'X-API-Key: ' . $this->apiKey,
				],
			],
		]);
		if (!$setResult) {
			throw new RuntimeException("Can't set stream context params to get contents from {$url}");
		}
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
		$result = [];
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
	 */
	private function storeKeys(string $ssid, array $keys): void
	{
		if (!$keys) {
			return;
		}

		$datetime = new DateTime();
		$this->database->beginTransaction();
		try {
			$timeZone = $datetime->getTimezone()->getName();
			$this->database->query(
				'INSERT INTO ssids',
				[
					'ssid' => $ssid,
					'added' => $datetime,
					'added_timezone' => $timeZone,
				],
			);
			$ssidId = $this->database->getInsertId();
			foreach ($keys as $key) {
				$this->database->query(
					'INSERT INTO `keys`',
					[
						'key_ssid' => $ssidId,
						'serial' => $key->getSerial(),
						'key' => $key->getKey(),
						'type' => $key->getType()->value,
					],
				);
			}
			$this->database->commit();
		} catch (UniqueConstraintViolationException) {
			$this->database->rollBack();
		} catch (PDOException $e) {
			$this->database->rollBack();
			throw $e;
		}
	}


	private function buildKey(string $serial, string $key, int $type): WiFiKey
	{
		preg_match('/^[a-z]+/i', $serial, $matches);
		$prefix = current($matches);
		if (!$prefix || !in_array($prefix, $this->serialNumberPrefixes)) {
			throw new RuntimeException('Unknown prefix for serial ' . $serial);
		}
		return new WiFiKey($serial, $prefix, null, null, $key, WiFiBand::from($type));
	}

}
