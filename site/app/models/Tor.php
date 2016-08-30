<?php
namespace MichalSpacekCz;

/**
 * Tor service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Tor
{

	/** @internal */
	const UA_DEFAULT = 'default';

	/** @var string */
	private $proxy;

	/** @var array */
	private $userAgents;


	/**
	 * @param string $proxy
	 */
	public function setProxy($proxy)
	{
		$this->proxy = $proxy;
	}


	/**
	 * @param array $userAgents
	 */
	public function setUserAgents(array $userAgents)
	{
		$this->userAgents = $userAgents;
	}


	/**
	 * Fetch data.
	 *
	 * @param string $companyId
	 * @param string $userAgentAlias
	 * @return string
	 */
	public function fetch($url, $userAgentAlias = self::UA_DEFAULT)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
		curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgents[$userAgentAlias]);
		$output = curl_exec($ch);
		$errNo = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch);
		if ($errNo !== 0) {
			throw new \RuntimeException($error, $errNo);
		}
		return $output;
	}


}
