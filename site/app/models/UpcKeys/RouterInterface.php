<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use stdClass;

interface RouterInterface
{

	/**
	 * @param string[] $prefixes
	 */
	public function setPrefixes(array $prefixes): void;

	public function setModel(string $model): void;


	/**
	 * @return array<string, array<integer, string>>
	 */
	public function getModelWithPrefixes(): array;


	/**
	 * @param string $ssid
	 * @return stdClass[] (serial, key, type)
	 */
	public function getKeys(string $ssid): array;

}
