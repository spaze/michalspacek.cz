<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use Nette\Database\Explorer;

class Ubee implements UpcWiFiRouter
{

	private const OUI_UBEE = '647c34';
	private const PREFIX = 'UAAP';


	public function __construct(
		private readonly Explorer $database,
	) {
	}


	public function getModelWithPrefixes(): array
	{
		return ['Ubee EVW3226' => [self::PREFIX]];
	}


	/**
	 * Get keys from database.
	 *
	 * @param string $ssid
	 * @return array<int, WiFiKey>
	 */
	public function getKeys(string $ssid): array
	{
		$rows = $this->database->fetchAll('SELECT mac, `key` FROM keys_ubee WHERE ssid = ?', substr($ssid, 3));
		$result = [];
		foreach ($rows as $row) {
			$result[$row->mac] = $this->buildKey($row->mac, (int)$row->key);
		}
		ksort($result);
		return array_values($result);
	}


	private function buildKey(int $mac, int $binaryKey): WiFiKey
	{
		$key = '';
		for ($i = 7; $i >= 0; $i--) {
			$key .= chr(($binaryKey >> $i * 5 & 0x1F) + 0x41);
		}
		return new WiFiKey(self::PREFIX, self::PREFIX, self::OUI_UBEE, sprintf('%06x', $mac), $key, WiFiBand::Unknown);
	}

}
