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
	const SSID_VALID_PATTERN = '([Uu][Pp][Cc])[0-9]{7}';

	/** @var string */
	const SSID_PLACEHOLDER = 'UPC1234567';

	/** @var integer */
	const SSID_TYPE_24GHZ = 1;

	/** @var integer */
	const SSID_TYPE_5GHZ = 2;

	/** @var integer */
	const SSID_TYPE_UNKNOWN = 3;

	/** @var array of \MichalSpacekCz\UpcKeys\RouterInterface */
	protected $routers;

	/** @var array of prefixes */
	protected $prefixes;

	/** @var array of model => prefixes */
	protected $modelsWithPrefixes;

	/** @var array of keys */
	protected $keys;


	/**
	 * @param \MichalSpacekCz\UpcKeys\RouterInterface $router
	 */
	public function addRouter(\MichalSpacekCz\UpcKeys\RouterInterface $router)
	{
		$this->routers[get_class($router)] = $router;
	}


	/**
	 * Call a method on all routers
	 *
	 * @param string $method
	 * @param string $args
	 * @param callable $callback
	 */
	private function routerCall($method, $args, callable $callback)
	{
		foreach ($this->routers as $router) {
			$callback($router->$method(...$args));
		}
	}


	/**
	 * Get serial number prefixes to get keys for.
	 *
	 * @return array of prefixes
	 */
	public function getPrefixes()
	{
		if ($this->prefixes === null) {
			$this->prefixes = [];
			$this->routerCall('getModelWithPrefixes', [], function ($prefixes) {
				$this->prefixes = array_merge($this->prefixes, current($prefixes));
			});
		}
		return $this->prefixes;
	}


	/**
	 * Get router models with serial number prefixes.
	 *
	 * @return array of models with prefixes
	 */
	public function getModelsWithPrefixes()
	{
		if ($this->modelsWithPrefixes === null) {
			$this->modelsWithPrefixes = [];
			$this->routerCall('getModelWithPrefixes', [], function ($prefixes) {
				$this->modelsWithPrefixes = array_merge($this->modelsWithPrefixes, $prefixes);
			});
		}
		return $this->modelsWithPrefixes;
	}


	/**
	 * Get keys, possibly from database.
	 *
	 * If the keys are not already in the database, store them.
	 *
	 * @param string
	 * @return false|array of \stdClass (serial, key, type)
	 */
	public function getKeys($ssid)
	{
		$this->keys = [];
		$this->routerCall('getKeys', [$ssid], function ($keys) {
			$this->keys = array_merge($this->keys, $keys);
		});
		return $this->keys;
	}


	/**
	 * Save keys to a database if not already there.
	 *
	 * @param string
	 * @return boolean
	 */
	public function saveKeys($ssid)
	{
		return $this->routers[UpcKeys\Technicolor::class]->saveKeys($ssid);
	}


	/**
	 * Return SSID validation pattern to detect whether the SSID is a valid one.
	 *
	 * @return string
	 */
	public function getValidSsidPattern()
	{
		return self::SSID_VALID_PATTERN;
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
