<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys;

use Nette\Application\Responses\TextResponse;
use Nette\Utils\Strings;

class UpcKeys
{

	private const SSID_VALID_PATTERN = '([Uu][Pp][Cc])[0-9]{7}';

	private const SSID_PLACEHOLDER = 'UPC1234567';

	/** @var array<class-string<UpcWiFiRouter>, UpcWiFiRouter> */
	private array $routers = [];

	/** @var list<string>|null */
	private ?array $prefixes = null;

	/** @var array<string, array<int, string>>|null */
	private ?array $modelsWithPrefixes = null;


	/**
	 * @param non-empty-list<UpcWiFiRouter> $routers
	 */
	public function __construct(array $routers)
	{
		foreach ($routers as $router) {
			$this->routers[$router::class] = $router;
		}
	}


	/**
	 * Get serial number prefixes to get keys for.
	 *
	 * @return list<string>
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
	 * Return SSID validation pattern to detect whether the SSID is a valid one.
	 */
	public function getValidSsidPattern(): string
	{
		return self::SSID_VALID_PATTERN;
	}


	/**
	 * Check whether the SSID is valid for upc_keys to work.
	 */
	public function isValidSsid(string $ssid): bool
	{
		// Inspired by Nette\Forms\Validator::validatePattern()
		return (bool)Strings::match($ssid, sprintf("\x01^(%s)\\z\x01u", self::SSID_VALID_PATTERN));
	}


	/**
	 * Return SSID placeholder.
	 */
	public function getSsidPlaceholder(): string
	{
		return self::SSID_PLACEHOLDER;
	}


	/**
	 * @param array<int, WiFiKey> $keys
	 */
	public function getTextResponse(?string $ssid, ?string $error, array $keys): TextResponse
	{
		$output = $ssid !== null ? "# {$ssid}\n" : '';
		$output .= $error !== null ? "# Error: {$error}\n" : '';
		foreach ($keys as $key) {
			$output .= $key->getKey() . "\n";
		}
		return new TextResponse($output);
	}

}
