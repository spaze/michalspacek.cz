<?php
abstract class BaseModel extends \Nette\Database\Table\Selection
{
	const TABLE_NAME = null;

	const TEXY_NAMESPACE = 'TexyFormatted';

	protected $formattedProperties = array();

	private $cache;

	public function __construct(\Nette\Database\Connection $connection, \Nette\Caching\IStorage $cacheStorage)
	{
		$this->cache = new \Nette\Caching\Cache($cacheStorage, self::TEXY_NAMESPACE);

		parent::__construct(static::TABLE_NAME, $connection);
	}

	protected function createRow(array $row)
	{
		foreach ($this->formattedProperties as $property) {
			$original = $row[$property];
			// Nette Cache itself generates the key by hashing the key passed in load() so we can use whatever we want
			$formatted = $this->cache->load($original, function() use ($original) {
				Texy::$advertisingNotice = false;
				$texy = new Texy();
				$texy->encoding = 'utf-8';
				$texy->allowedTags = Texy::NONE;
				return preg_replace('~^\s*<p[^>]*>(.*)</p>\s*$~s', '$1', $texy->process($original));
			});
			$row[$property . self::TEXY_NAMESPACE] = $formatted;
		}
		return parent::createRow($row);
	}
}
