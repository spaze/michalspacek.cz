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
	const SSID_UPC_PATTERN = '([Uu][Pp][Cc]).*';

	/** @var string */
	const SSID_VALID_PATTERN = '([Uu][Pp][Cc])[0-9]{7}';

	/** @var string */
	const SSID_PLACEHOLDER = 'UPC1234567';

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


	/**
	 * Return SSID validation pattern to detect whether the SSID is a UPC one.
	 *
	 * @return string
	 */
	public function getUpcSsidPattern()
	{
		return self::SSID_UPC_PATTERN;
	}


	/**
	 * Check whether the SSID is UPC SSID.
	 *
	 * @param string
	 * @return string
	 */
	public function isUpcSsid($ssid)
	{
		// Inspired by Nette\Forms\Validator::validatePattern()
		return (bool)\Nette\Utils\Strings::match($ssid, sprintf("\x01^(%s)\\z\x01u", self::SSID_UPC_PATTERN));
	}


	/**
	 * Check whether the SSID is valid for upc_keys to work.
	 *
	 * @param string
	 * @return string
	 */
	public function isValidSsid($ssid)
	{
		// Inspired by Nette\Forms\Validator::validatePattern()
		return (bool)\Nette\Utils\Strings::match($ssid, sprintf("\x01^(%s)\\z\x01u", self::SSID_VALID_PATTERN));
	}


	/**
	 * Return SSID placeholder.
	 *
	 * @return string
	 */
	public function getSsidPlaceholder()
	{
		return self::SSID_PLACEHOLDER;
	}


}
