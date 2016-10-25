<?php
namespace MichalSpacekCz\Tor;

/**
 * Tor proxy service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Proxy
{

	/** @var \MichalSpacekCz\Tor\Control */
	private $control;

	/** @var string */
	private $proxy;

	/** @var array */
	private $transferInfo = array();

	/** @var string */
	private $userAgent;

	/** @var array */
	private $exitNodes = array();


	/** @var integer */
	private $attempt;


	/**
	 * Constructor.
	 *
	 * @param \MichalSpacekCz\Tor\Control $control
	 */
	public function __construct(Control $control)
	{
		$this->control = $control;
	}


	/**
	 * @param string $host
	 * @param integer $port
	 */
	public function setProxy($host, $port)
	{
		$this->proxy = "{$host}:{$port}";
	}


	/**
	 * Set user agent to be used in subsequent requests.
	 *
	 * @param string $userAgentAlias
	 */
	public function setUserAgent($userAgent)
	{
		$this->userAgent = $userAgent;
		return $this;
	}


	/**
	 * Set exit ndoes to be used in subsequent requests.
	 *
	 * @param array $exitNodes
	 */
	public function setExitNodes(array $exitNodes)
	{
		$this->exitNodes = $exitNodes;
	}


	/**
	 * Fetch data.
	 *
	 * @param string $companyId
	 * @return string
	 */
	public function fetch($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
		curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);

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
			$this->control->cleanCircuits();
		}
		curl_close($ch);
		return $output;
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
