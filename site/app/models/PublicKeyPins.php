<?php
namespace MichalSpacekCz;

/**
 * PublicKeyPins service (for HPKP).
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class PublicKeyPins
{

	/** @var array of host => array of pins */
	protected $pins = array();


	public function setPins($pins)
	{
		foreach ($pins as $host => $sources) {
			$this->pins[$host] = $sources;
		}
	}


	public function getHeader($host)
	{
		if (!isset($this->pins[$host])) {
			return false;
		}

		$directives = array();
		foreach ($this->pins[$host] as $directive => $value) {
			switch ($directive) {
				case 'pins':
					foreach ($value as $hash) {
						$directives[] = sprintf('pin-sha256="%s"', $hash);
					}
					break;
				case 'report-uri':
					$directives[] = sprintf('%s="%s"', $directive, $value);
					break;
				default:
					if (empty($value)) {
						$directives[] = $directive;
					} else {
						$directives[] = trim("{$directive}={$value}");
					}
					break;
			}
		}
		return (isset($this->pins[$host]) ? implode('; ', $directives) : false);
	}

}
