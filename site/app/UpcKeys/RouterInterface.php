<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use stdClass;

interface RouterInterface
{

	/**
	 * @return array<string, array<int, string>>
	 */
	public function getModelWithPrefixes(): array;


	/**
	 * @param string $ssid
	 * @return stdClass[] (serial, key, type)
	 */
	public function getKeys(string $ssid): array;

}
