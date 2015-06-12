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

	/** @var array of host => array of policies */
	protected $policy = array();


	public function setPolicy($policy)
	{
		foreach ($policy as $host => $sources) {
			$this->policy[$host] = $sources;
		}
	}


	public function getHeader($host)
	{
		if (!isset($this->policy[$host])) {
			return false;
		}

		$policy = array();
		foreach ($this->policy[$host] as $directive => $sources) {
			$policy[] = "{$directive} {$sources}";
		}
		return (isset($this->policy[$host]) ? implode('; ', $policy) : false);
	}

}
