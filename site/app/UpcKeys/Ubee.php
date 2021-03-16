<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use Nette\Database\Explorer;
use RuntimeException;
use stdClass;

class Ubee implements RouterInterface
{

	/** @var string */
	private const OUI_UBEE = '647c34';

	private Explorer $database;

	private string $prefix;

	private string $model;


	public function __construct(Explorer $context)
	{
		$this->database = $context;
	}


	/**
	 * @param string[] $prefixes
	 */
	public function setPrefixes(array $prefixes): void
	{
		if (count($prefixes) !== 1) {
			throw new RuntimeException('Ubee can has only one prefix');
		}
		$this->prefix = current($prefixes);
	}


	public function setModel(string $model): void
	{
		$this->model = $model;
	}


	/**
	 * Get serial number prefixes to get keys for.
	 *
	 * @return array<string, array<integer, string>>
	 */
	public function getModelWithPrefixes(): array
	{
		return [$this->model => [$this->prefix]];
	}


	/**
	 * Get keys from database.
	 *
	 * @param string $ssid
	 * @return stdClass[] (serial, key, type)
	 */
	public function getKeys(string $ssid): array
	{
		$rows = $this->database->fetchAll('SELECT mac, `key` FROM keys_ubee WHERE ssid = ?', substr($ssid, 3));
		$result = array();
		foreach ($rows as $row) {
			$result[$row->mac] = $this->buildKey($row->mac, (int)$row->key);
		}
		ksort($result);
		return array_values($result);
	}


	private function buildKey(int $mac, int $key): stdClass
	{
		$result = new stdClass();
		$result->serial = $this->prefix;
		$result->oui = self::OUI_UBEE;
		$result->mac = sprintf('%06x', $mac);
		$result->type = UpcKeys::SSID_TYPE_UNKNOWN;

		$result->key = '';
		for ($i = 7; $i >= 0; $i--) {
			$result->key .= chr((($key >> $i * 5) & 0x1F) + 0x41);
		}

		return $result;
	}

}
