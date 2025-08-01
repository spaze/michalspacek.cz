<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use Composer\Pcre\Regex;
use DateTime;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Http\Client\HttpClient;
use MichalSpacekCz\Http\Client\HttpClientRequest;
use MichalSpacekCz\Http\Exceptions\HttpClientRequestException;
use MichalSpacekCz\UpcKeys\Exceptions\UpcKeysApiException;
use MichalSpacekCz\UpcKeys\Exceptions\UpcKeysApiIncorrectTokensException;
use MichalSpacekCz\UpcKeys\Exceptions\UpcKeysApiResponseInvalidException;
use Nette\Database\Explorer;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Override;
use PDOException;
use Tracy\Debugger;

final readonly class Technicolor implements UpcWiFiRouter
{

	private const array PREFIXES = [0 => 'SAAP', 1 => 'SAPP', 2 => 'SBAP'];


	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
		private HttpClient $httpClient,
		private UpcKeysStorageConversions $conversions,
		private string $apiUrl,
		private string $apiKey,
	) {
	}


	#[Override]
	public function getModelWithPrefixes(): array
	{
		return ['Technicolor TC7200' => self::PREFIXES];
	}


	/**
	 * Get keys, possibly from database.
	 *
	 * If the keys are not already in the database, store them.
	 *
	 * @return array<int, WiFiKey>
	 * @throws HttpClientRequestException
	 */
	#[Override]
	public function getKeys(string $ssid): array
	{
		try {
			$keys = $this->fetchKeys($ssid);
			if ($keys === []) {
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
	 * Get possible keys and serial for an SSID.
	 *
	 * @param string $ssid
	 * @return array<int, WiFiKey>
	 * @throws UpcKeysApiIncorrectTokensException
	 * @throws UpcKeysApiResponseInvalidException
	 * @throws HttpClientRequestException
	 */
	private function generateKeys(string $ssid): array
	{
		$request = new HttpClientRequest(sprintf($this->apiUrl, $ssid, implode(',', self::PREFIXES)));
		$request->addHeader('X-API-Key', $this->apiKey);
		$json = $this->httpClient->get($request)->getBody();
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
			if ($line === '') {
				continue;
			}

			$result = Regex::matchStrictGroups('/([A-Z]+)([^,]+),([^,]+),(\d+)/', $line);
			if (!$result->matched) {
				throw new UpcKeysApiIncorrectTokensException($json, $line);
			}
			[, $prefix, $serial, $key, $type] = $result->matches;
			$serial = $this->padSerial($serial);
			$keys["{$type}-{$prefix}{$serial}"] = new WiFiKey($prefix, $serial, null, null, $key, WiFiBand::from((int)$type));
		}
		ksort($keys);
		return array_values($keys);
	}


	/**
	 * Fetch keys from database.
	 *
	 * @param string $ssid
	 * @return array<int, WiFiKey>
	 */
	private function fetchKeys(string $ssid): array
	{
		$rows = $this->typedDatabase->fetchAll(
			'SELECT
				k.prefix_id AS prefixId,
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
			assert(is_int($row->prefixId));
			assert(is_int($row->serial));
			assert(is_int($row->key));
			assert(is_int($row->type));
			$serial = $this->padSerial((string)$row->serial);
			$prefix = self::PREFIXES[$row->prefixId];
			$result["{$row->type}-{$prefix}{$serial}"] = new WiFiKey(
				$prefix,
				$serial,
				null,
				null,
				$this->conversions->getKeyFromBinary($row->key),
				WiFiBand::from($row->type),
			);
		}
		ksort($result);
		return array_values($result);
	}


	/**
	 * Store keys to database.
	 *
	 * @param string $ssid
	 * @param array<int, WiFiKey> $keys
	 */
	private function storeKeys(string $ssid, array $keys): void
	{
		if ($keys === []) {
			return;
		}

		$prefixIds = array_flip(self::PREFIXES);
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
						'prefix_id' => $prefixIds[$key->getSerialPrefix()],
						'serial' => (int)$key->getSerial(),
						'key' => $this->conversions->getBinaryFromKey($key->getKey()),
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


	private function padSerial(string $serial): string
	{
		return str_pad($serial, 8, '0', STR_PAD_LEFT);
	}

}
