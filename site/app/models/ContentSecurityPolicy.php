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
	protected $rootDomain;

	/** @var array of host => array of policies */
	protected $policy = array();

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;


	public function __construct(\Nette\Http\IRequest $httpRequest)
	{
		$this->httpRequest = $httpRequest;
	}


	public function setRootDomain($rootDomain)
	{
		$this->rootDomain = $rootDomain;
	}


	public function setPolicy($policy)
	{
		foreach ($policy as $host => $sources) {
			$this->policy["{$host}.{$this->rootDomain}"] = $sources;
		}
	}


	public function getHeader()
	{
		$host = $this->httpRequest->getUrl()->getHost();

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
