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
	const DEFAULT_PRESENTER = 'homepage';

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
	 * @param  string $presenter
	 * @return string
	 */
	public function getHeader($presenter)
	{
		$policy = array();
		$presenter = strtolower(str_replace(':', '_', $presenter));
		foreach (($this->policy[$presenter] ?? $this->policy[self::DEFAULT_PRESENTER]) as $directive => $sources) {
			if (is_int($directive)) {
				foreach ($sources as $key => $value) {
					$policy[$key] = trim("$key " . $this->flattenSources($value));
				}
			} else {
				$policy[$directive] = trim("$directive " . $this->flattenSources($sources));
			}
		}
		return implode('; ', $policy);
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
			$items = [];
			array_walk_recursive($sources, function($value) use (&$items) {
				$items[] = $value;
			});
			$sources = implode(' ', $items);
		}
		return $sources;
	}

}
