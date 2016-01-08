<?php
namespace MichalSpacekCz;

/**
 * UPC Keys service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class UpcKeys
{

	/** @var string */
	protected $url;

	/** @var string */
	protected $apiKey;


	/**
	 * Set URL used by API Gateway.
	 *
	 * @param string
	 */
	public function setUrl($url)
	{
		$this->url = $url;
	}


	/**
	 * Set API Key used by API Gateway.
	 *
	 * @param string
	 */
	public function setApiKey($apiKey)
	{
		$this->apiKey = $apiKey;
	}


	/**
	 * Get possible keys and serial for an SSID.
	 *
	 * @param string
	 * @return array (serial, key)
	 */
	public function getKeys($ssid)
	{
		$url = sprintf($this->url, $ssid);
		$data = file_get_contents($url, false, stream_context_create(['http' => ['header' => 'X-API-Key: ' . $this->apiKey]]));
		$data = \Nette\Utils\Json::decode($data);
		$keys = array();
		foreach (explode("\n", $data) as $line) {
			if (empty($line)) {
				continue;
			}

			list($serial, $key) = explode(',', $line);
			$keys[$serial] = $key;
		}
		return $keys;
	}


}
