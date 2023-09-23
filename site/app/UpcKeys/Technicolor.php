<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use DateTime;
use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Http\Client\HttpClientRequest;
use MichalSpacekCz\Http\Exceptions\HttpClientGetException;
use MichalSpacekCz\UpcKeys\Exceptions\UpcKeysApiException;
use MichalSpacekCz\UpcKeys\Exceptions\UpcKeysApiIncorrectTokensException;
use MichalSpacekCz\UpcKeys\Exceptions\UpcKeysApiResponseInvalidException;
use MichalSpacekCz\UpcKeys\Exceptions\UpcKeysApiUnknownPrefixException;
use Nette\Database\Explorer;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use PDOException;
use Tracy\Debugger;

class Technicolor implements RouterInterface
{

	/**
	 * @param string[] $serialNumberPrefixes
	 */
	public function __construct(
		private readonly Explorer $database,
		private readonly HttpClient $httpClient,
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
	 * @throws HttpClientGetException
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
		} catch (UpcKeysApiException $e) {
			Debugger::log($e);
			return [];
		}
	}


	/**
	 * Save keys to a database if not already there.
	 *
	 * @throws HttpClientGetException
	 */
	public function saveKeys(string $ssid): bool
	{
		try {
			if (!$this->hasKeys($ssid)) {
				$this->storeKeys($ssid, $this->generateKeys($ssid));
			}
			return true;
		} catch (UpcKeysApiException $e) {
			Debugger::log($e);
			return false;
		}
	}


	/**
	 * Get possible keys and serial for an SSID.
	 *
	 * @param string $ssid
	 * @return array<int, WiFiKey>
	 * @throws UpcKeysApiIncorrectTokensException
	 * @throws UpcKeysApiResponseInvalidException
	 * @throws UpcKeysApiUnknownPrefixException
	 * @throws HttpClientGetException
	 */
	private function generateKeys(string $ssid): array
	{
		$request = new HttpClientRequest(sprintf($this->apiUrl, $ssid, implode(',', $this->serialNumberPrefixes)));
		$request->addHeader('X-API-Key', $this->apiKey);
		$json = $this->httpClient->get($request);
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
				throw new UpcKeysApiIncorrectTokensException($json, $line);
			}
			[, $serial, $key, $type] = $matches;
			$keys["{$type}-{$serial}"] = $this->buildKey($serial, $key, (int)$type);
		}
		ksort($keys);
		return array_values($keys);
	}


	/**
	 * Fetch keys from database.
	 *
	 * @param string $ssid
	 * @return array<int, WiFiKey>
	 * @throws UpcKeysApiUnknownPrefixException
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


	/**
	 * @throws UpcKeysApiUnknownPrefixException
	 */
	private function buildKey(string $serial, string $key, int $type): WiFiKey
	{
		preg_match('/^[a-z]+/i', $serial, $matches);
		$prefix = current($matches);
		if (!$prefix || !in_array($prefix, $this->serialNumberPrefixes)) {
			throw new UpcKeysApiUnknownPrefixException($serial);
		}
		return new WiFiKey($serial, $prefix, null, null, $key, WiFiBand::from($type));
	}

}
