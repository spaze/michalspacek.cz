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

	/** @var string */
	private $controlHost;

	/** @var integer */
	private $controlPort;

	/** @var string */
	private $controlPassword;

	/** @var array */
	private $userAgents;

	/** @var array */
	private $transferInfo = array();


	/**
	 * @param string $host
	 * @param integer $port
	 */
	public function setProxy($host, $port)
	{
		$this->proxy = "{$host}:{$port}";
	}


	/**
	 * @param string $host
	 * @param integer $port
	 * @param string $password
	 */
	public function setControl($host, $port, $password)
	{
		$this->controlHost = $host;
		$this->controlPort = $port;
		$this->controlPassword = $password;
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
		if ($errNo !== 0) {
			curl_close($ch);
			throw new \RuntimeException($error, $errNo);
		}
		$this->transferInfo = curl_getinfo($ch);

		curl_close($ch);
		return $output;
	}


	/**
	 * Request new Tor circuits.
	 *
	 * @return null
	 */
	public function cleanCircuits()
	{
		$fp = fsockopen($this->controlHost, $this->controlPort, $errNo, $errStr);
		if ($fp === false) {
			throw new \RuntimeException("Can't connect to control port: {$errStr}", $errNo);
		}
		fputs($fp, "AUTHENTICATE \"{$this->controlPassword}\"\r\n");
		list($code, $message) = explode(' ', fread($fp, 1024), 2);
		if ($code != 250) {
			throw new \RuntimeException("Auth failed: {$message}", $code);
		}
		fputs($fp, "SIGNAL NEWNYM\r\n");
		fclose($fp);
	}


	/**
	 * Return cURL transfer info.
	 *
	 * @param integer $opt see curl_getinfo() for detailed description
	 * @return string
	 */
	public function getTransferInfo($opt = null)
	{
		if ($opt === null) {
			return $this->transferInfo;
		} else {
			return $this->transferInfo[$opt];
		}
	}


	/**
	 * Return last HTTP code from cURL
	 *
	 * @return string
	 */
	public function getLastHttpCode()
	{
		return $this->getTransferInfo('http_code');
	}

}
