<?php
namespace Bare\Next\Formatter;

class Texy
{

	const TEXY_NAMESPACE = 'TexyFormatted';

	/**
	 * @var Nette\Caching\IStorage
	 */
	protected $cacheStorage;


	public function __construct(\Nette\Caching\IStorage $cacheStorage)
	{
		$this->cacheStorage = $cacheStorage;
	}


	/**
	 * @return \Nette\Utils\Html
	 */
	public function format($text)
	{
		$cache = new \Nette\Caching\Cache($this->cacheStorage, self::TEXY_NAMESPACE);

		// Nette Cache itself generates the key by hashing the key passed in load() so we can use whatever we want
		$formatted = $cache->load($text, function() use ($text) {
			\Texy::$advertisingNotice = false;
			$texy = new \Texy();
			$texy->encoding = 'utf-8';
			$texy->allowedTags = \Texy::NONE;
			return preg_replace('~^\s*<p[^>]*>(.*)</p>\s*$~s', '$1', $texy->process($text));
		});
		return \Nette\Utils\Html::el()->setHtml($formatted);;
	}


}