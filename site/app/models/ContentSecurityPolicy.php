<?php
namespace MichalSpacekCz;

/**
 * ContentSecurityPolicy service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ContentSecurityPolicy
{

	/** @var string */
	const DEFAULT_PRESENTER = 'default';

	/** @var array of host => array of policies */
	protected $policy = array();


	public function setPolicy($policy)
	{
		foreach ($policy as $host => $sources) {
			$this->policy[$host] = $sources;
		}
	}


	/**
	 * Get Content-Security-Policy header value.
	 *
	 * @param  string $host
	 * @param  string $presenter
	 * @return string
	 */
	public function getHeader($host, $presenter)
	{
		if (!isset($this->policy[$host])) {
			return false;
		}

		$policy = array();
		foreach (($this->policy[$host][strtolower($presenter)] ?? $this->policy[$host][self::DEFAULT_PRESENTER]) as $directive => $sources) {
			if (is_int($directive)) {
				foreach ($sources as $key => $value) {
					$policy[] = trim("$key " . $this->flattenSources($value));
				}
			} else {
				$policy[] = trim("$directive " . $this->flattenSources($sources));
			}
		}
		return (isset($this->policy[$host]) ? implode('; ', $policy) : false);
	}


	/**
	 * Make string from (possible) arrays.
	 *
	 * @param  string|array $sources
	 * @return string
	 */
	private function flattenSources($sources)
	{
		if (is_array($sources)) {
			$sources = implode(' ', $sources);
		}
		return $sources;
	}

}
