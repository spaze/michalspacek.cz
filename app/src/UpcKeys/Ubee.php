<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use Nette\Database\Explorer;
use Override;

final readonly class Ubee implements UpcWiFiRouter
{

	private const string OUI_UBEE = '647c34';
	private const string PREFIX = 'UAAP';


	public function __construct(
		private Explorer $database,
		private UpcKeysStorageConversions $conversions,
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
		$rows = $this->database->fetchAll('SELECT mac, `key` FROM keys_ubee WHERE ssid = ?', substr($ssid, 3));
		$result = [];
		foreach ($rows as $row) {
			assert(is_int($row->mac));
			assert(is_int($row->key));
			$result[$row->mac] = new WiFiKey(
				self::PREFIX,
				'',
				self::OUI_UBEE,
				sprintf('%06x', $row->mac),
				$this->conversions->getKeyFromBinary($row->key),
				WiFiBand::Unknown,
			);
		}
		ksort($result);
		return array_values($result);
	}

}
