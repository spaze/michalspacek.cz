<?php
declare(strict_types = 1);

namespace Netxten\Formatter;

use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\Html;
use Throwable;

/**
 * Caching Texy formatter.
 */
class Texy
{

	/** @var string */
	public const DEFAULT_NAMESPACE = 'TexyFormatted';

	private string $namespace;

	protected bool $cacheResult = true;

	/** @var array<string, callable> */
	private array $handlers = [];

	protected Storage $cacheStorage;


	public function __construct(Storage $cacheStorage, string $namespace = self::DEFAULT_NAMESPACE)
	{
		$this->cacheStorage = $cacheStorage;
		$this->namespace = $namespace;
	}


	/**
	 * Create Texy object.
	 *
	 * @return \Texy\Texy
	 */
	protected function getTexy(): \Texy\Texy
	{
		$texy = new \Texy\Texy();
		$texy->allowedTags = $texy::NONE;
		foreach ($this->handlers as $event => $callback) {
			$texy->addHandler($event, $callback);
		}
		return $texy;
	}


	/**
	 * Add new event handler.
	 *
	 * @param string $event
	 * @param callable $callback
	 */
	protected function addHandler(string $event, callable $callback): void
	{
		$this->handlers = array_merge($this->handlers, [$event => $callback]);
	}


	/**
	 * Disable formatter cache.
	 *
	 * @return static
	 */
	public function disableCache(): self
	{
		$this->cacheResult = false;
		return $this;
	}


	/**
	 * Enable formatter cache.
	 *
	 * @return static
	 */
	public function enableCache(): self
	{
		$this->cacheResult = true;
		return $this;
	}


	/**
	 * Cache formatted string.
	 *
	 * @param string $text
	 * @param callable $callback
	 * @return Html
	 * @throws Throwable
	 */
	private function cache(string $text, callable $callback): Html
	{
		if ($this->cacheResult) {
			$cache = new Cache($this->cacheStorage, $this->namespace);
			// Nette Cache itself generates the key by hashing the key passed in load() so we can use whatever we want
			$formatted = $cache->load($text, $callback);
		} else {
			$formatted = $callback();
		}
		return Html::el()->setHtml($formatted);
	}


	/**
	 * Format string and strip surrounding P element.
	 *
	 * Suitable for "inline" strings like headers.
	 *
	 * @param string|null $text Text to format
	 * @param \Texy\Texy|null $texy
	 * @return Html|null
	 * @throws Throwable
	 */
	public function format(?string $text, ?\Texy\Texy $texy = null): ?Html
	{
		return (empty($text) ? null : $this->cache("{$text}|" . __FUNCTION__, function () use ($text, $texy) {
			return preg_replace('~^\s*<p[^>]*>(.*)</p>\s*$~s', '$1', ($texy ?? $this->getTexy())->process($text));
		}));
	}


	/**
	 * Format string.
	 *
	 * @param string|null $text Text to format
	 * @param \Texy\Texy|null $texy
	 * @return Html|null
	 * @throws Throwable
	 */
	public function formatBlock(?string $text, ?\Texy\Texy $texy = null): ?Html
	{
		return (empty($text) ? null : $this->cache("{$text}|" . __FUNCTION__, function () use ($text, $texy) {
			return ($texy ?? $this->getTexy())->process($text);
		}));
	}

}
