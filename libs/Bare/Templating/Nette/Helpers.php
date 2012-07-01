<?php
namespace Bare\Nette\Templating;

class Helpers extends \Nette\Object
{
	const TEXY_NAMESPACE = 'TexyFormatted';

	/** @var Nette\DI\Container */
	private $context;

	private $localDateSubstitution = array(
		'%B' => '%m',
	);

	private $localDateFormat = array(
		'cs' => array(
			'%m' => array(
				'01' => 'leden',
				'02' => 'únor',
				'03' => 'březen',
				'04' => 'duben',
				'05' => 'květen',
				'06' => 'červen',
				'07' => 'červenec',
				'08' => 'srpen',
				'09' => 'září',
				'10' => 'říjen',
				'11' => 'listopad',
				'12' => 'prosinec',
			),
		),
	);


	public function __construct(\Nette\DI\Container $context)
	{
		$this->context = $context;
	}


	public function loader($helper)
	{
		if (method_exists($this, $helper)) {
			return callback($this, $helper);
		}
	}


	public function localDate($time, $language, $format = null)
	{
		$time = \Nette\DateTime::from($time);

		$replace = array();
		foreach ($this->localDateSubstitution as $key => $value) {
			$substituted   = strftime($value, $time->format('U'));
			$replace[$key] = str_replace('%', '%%', $this->localDateFormat[$language][$value][$substituted]);
		}

		return \Nette\Templating\Helpers::date($time, strtr($format, $replace));
	}


	public function texy($text)
	{
		$cache = new \Nette\Caching\Cache($this->context->getService('cacheStorage'), self::TEXY_NAMESPACE);

		// Nette Cache itself generates the key by hashing the key passed in load() so we can use whatever we want
		$formatted = $cache->load($text, function() use ($text) {
			\Texy::$advertisingNotice = false;
			$texy = new \Texy();
			$texy->encoding = 'utf-8';
			$texy->allowedTags = \Texy::NONE;
			return preg_replace('~^\s*<p[^>]*>(.*)</p>\s*$~s', '$1', $texy->process($text));
		});
		return $formatted;
	}


}