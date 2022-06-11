<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use Nette\Utils\Strings;

class UpcKeys
{

	/** @var string */
	private const SSID_VALID_PATTERN = '([Uu][Pp][Cc])[0-9]{7}';

	/** @var string */
	private const SSID_PLACEHOLDER = 'UPC1234567';

	/** @var int */
	public const SSID_TYPE_24GHZ = 1;

	/** @var int */
	public const SSID_TYPE_5GHZ = 2;

	/** @var int */
	public const SSID_TYPE_UNKNOWN = 3;

	/** @var RouterInterface[] */
	private array $routers;

	/** @var string[]|null */
	private ?array $prefixes = null;

	/** @var array<string, array<int, string>>|null */
	private ?array $modelsWithPrefixes = null;


	public function __construct(RouterInterface ...$routers)
	{
		foreach ($routers as $router) {
			$this->routers[$router::class] = $router;
		}
	}


	/**
	 * Get serial number prefixes to get keys for.
	 *
	 * @return string[]
	 */
	public function getPrefixes(): array
	{
		if ($this->prefixes === null) {
			$this->prefixes = [];
			foreach ($this->routers as $router) {
				$this->prefixes = array_merge($this->prefixes, current($router->getModelWithPrefixes()));
			}
		}
		return $this->prefixes;
	}


	/**
	 * Get router models with serial number prefixes.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function getModelsWithPrefixes(): array
	{
		if ($this->modelsWithPrefixes === null) {
			$this->modelsWithPrefixes = [];
			foreach ($this->routers as $router) {
				$this->modelsWithPrefixes = array_merge($this->modelsWithPrefixes, $router->getModelWithPrefixes());
			}
		}
		return $this->modelsWithPrefixes;
	}


	/**
	 * Get keys, possibly from database.
	 *
	 * If the keys are not already in the database, store them.
	 *
	 * @param string $ssid
	 * @return array<int, WiFiKey>
	 */
	public function getKeys(string $ssid): array
	{
		$keys = [];
		foreach ($this->routers as $router) {
			$keys = array_merge($keys, $router->getKeys($ssid));
		}
		return $keys;
	}


	/**
	 * Save keys to a database if not already there.
	 *
	 * @param string $ssid
	 * @return bool
	 */
	public function saveKeys(string $ssid): bool
	{
		/** @var Technicolor $router */
		$router = $this->routers[Technicolor::class];
		return $router->saveKeys($ssid);
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
	 * @return bool
	 */
	public function isValidSsid(string $ssid): bool
	{
		// Inspired by Nette\Forms\Validator::validatePattern()
		return (bool)Strings::match($ssid, sprintf("\x01^(%s)\\z\x01u", self::SSID_VALID_PATTERN));
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
