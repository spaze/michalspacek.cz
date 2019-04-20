<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

class UpcKeys
{

	/** @var string */
	private const SSID_VALID_PATTERN = '([Uu][Pp][Cc])[0-9]{7}';

	/** @var string */
	private const SSID_PLACEHOLDER = 'UPC1234567';

	/** @var integer */
	public const SSID_TYPE_24GHZ = 1;

	/** @var integer */
	public const SSID_TYPE_5GHZ = 2;

	/** @var integer */
	public const SSID_TYPE_UNKNOWN = 3;

	/** @var array of \MichalSpacekCz\UpcKeys\RouterInterface */
	protected $routers;

	/** @var array of prefixes */
	protected $prefixes;

	/** @var array of model => prefixes */
	protected $modelsWithPrefixes;

	/** @var array of keys */
	protected $keys;


	public function addRouter(\MichalSpacekCz\UpcKeys\RouterInterface $router): void
	{
		$this->routers[get_class($router)] = $router;
	}


	/**
	 * Call a method on all routers
	 *
	 * @param string $method
	 * @param array $args
	 * @param callable $callback
	 */
	private function routerCall(string $method, array $args, callable $callback): void
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
	public function getPrefixes(): array
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
	public function getModelsWithPrefixes(): array
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
	 * @param string $ssid
	 * @return \stdClass[] (serial, key, type)
	 */
	public function getKeys(string $ssid): array
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
	 * @param string $ssid
	 * @return boolean
	 */
	public function saveKeys(string $ssid): bool
	{
		return $this->routers[UpcKeys\Technicolor::class]->saveKeys($ssid);
	}


	/**
	 * Return SSID validation pattern to detect whether the SSID is a valid one.
	 *
	 * @return string
	 */
	public function getValidSsidPattern(): string
	{
		return self::SSID_VALID_PATTERN;
	}


	/**
	 * Check whether the SSID is valid for upc_keys to work.
	 *
	 * @param string $ssid
	 * @return boolean
	 */
	public function isValidSsid(string $ssid): bool
	{
		// Inspired by Nette\Forms\Validator::validatePattern()
		return (bool)\Nette\Utils\Strings::match($ssid, sprintf("\x01^(%s)\\z\x01u", self::SSID_VALID_PATTERN));
	}


	/**
	 * Return SSID placeholder.
	 *
	 * @return string
	 */
	public function getSsidPlaceholder(): string
	{
		return self::SSID_PLACEHOLDER;
	}

}
