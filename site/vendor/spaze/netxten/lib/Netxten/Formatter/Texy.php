<?php
declare(strict_types = 1);

namespace Netxten\Formatter;

use \Nette\Utils\Html;

/**
 * Caching Texy formatter.
 */
class Texy
{

	/** @var string */
	const DEFAULT_NAMESPACE = 'TexyFormatted';

	/** @var string */
	private $namespace;

	/** @var boolean */
	protected $cacheResult = true;

	/** @var array $event => $callback */
	private $handlers = array();

	/** @var Nette\Caching\IStorage */
	protected $cacheStorage;


	/**
	 * Constructor.
	 *
	 * @param \Nette\Caching\IStorage $cacheStorage
	 * @param string $namespace
	 */
	public function __construct(\Nette\Caching\IStorage $cacheStorage, string $namespace = self::DEFAULT_NAMESPACE)
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
	 * @var string $text
	 * @var callable $callback
	 * @return Html
	 */
	private function cache(string $text, callable $callback): Html
	{
		if ($this->cacheResult) {
			$cache = new \Nette\Caching\Cache($this->cacheStorage, $this->namespace);
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
	 * @return Html|null
	 */
	public function format(?string $text): ?Html
	{
		return (empty($text) ? null : $this->cache("{$text}|" . __FUNCTION__, function() use ($text) {
			$texy = $this->getTexy();
			return preg_replace('~^\s*<p[^>]*>(.*)</p>\s*$~s', '$1', $texy->process($text));
		}));
	}


	/**
	 * Format string.
	 *
	 * @param string|null $text Text to format
	 * @return Html|null
	 */
	public function formatBlock(?string $text): ?Html
	{
		return (empty($text) ? null : $this->cache("{$text}|" . __FUNCTION__, function() use ($text) {
			return $this->getTexy()->process($text);
		}));
	}

}
