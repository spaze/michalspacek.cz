<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

interface UpcWiFiRouter
{

	/**
	 * @return non-empty-array<string, array<int, string>>
	 */
	public function getModelWithPrefixes(): array;


	/**
	 * @param string $ssid
	 * @return array<int, WiFiKey>
	 */
	public function getKeys(string $ssid): array;

}
