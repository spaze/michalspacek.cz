<?php
namespace MichalSpacekCz\UpcKeys;

/**
 * UPC router model interface.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
interface RouterInterface
{

	public function setPrefixes(array $prefixes);

	public function setModel($model);

	public function getModelWithPrefixes();

	public function getKeys($ssid);

}
