<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use MichalSpacekCz\Database\TypedDatabase;
use Override;

final readonly class Ubee implements UpcWiFiRouter
{

	private const string OUI_UBEE = '647c34';
	private const string PREFIX = 'UAAP';


	public function __construct(
		private TypedDatabase $typedDatabase,
	) {
	}


	#[Override]
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
	#[Override]
	public function getKeys(string $ssid): array
	{
		$rows = $this->typedDatabase->fetchAll('SELECT mac, `key` FROM keys_ubee WHERE ssid = ?', substr($ssid, 3));
		$result = [];
		foreach ($rows as $row) {
			assert(is_int($row->mac));
			assert(is_int($row->key));
			$result[$row->mac] = $this->buildKey($row->mac, $row->key);
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
