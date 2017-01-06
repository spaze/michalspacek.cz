<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

/**
 * UPC router model interface.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
interface RouterInterface
{

	public function setPrefixes(array $prefixes): void;

	public function setModel(string $model): void;

	public function getModelWithPrefixes(): array;

	public function getKeys(string $ssid): array;

}
