<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\DateTime\DateTimeFactory;
use Nette\Database\Explorer;

readonly class Sites
{

	public const string ALL = 'all';


	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
		private DateTimeFactory $dateTimeFactory,
	) {
	}


	/**
	 * @return list<Site>
	 */
	public function getAll(): array
	{
		$rows = $this->typedDatabase->fetchAll('SELECT id, url, alias FROM sites ORDER BY alias');
		$sites = [];
		foreach ($rows as $row) {
			assert(is_int($row->id));
			assert(is_string($row->url));
			assert(is_string($row->alias));
			$sites[] = new Site($row->id, $row->url, $row->alias);
		}
		return $sites;
	}


	public function getByUrl(string $url): ?Site
	{
		$row = $this->database->fetch('SELECT id, url, alias FROM sites WHERE url = ?', $url);
		if (!$row) {
			return null;
		}
		assert(is_int($row->id));
		assert(is_string($row->url));
		assert(is_string($row->alias));

		return new Site($row->id, $row->url, $row->alias);
	}


	/**
	 * Add site.
	 *
	 * @return int Id of newly inserted site
	 */
	public function add(string $url, string $alias, string $sharedWith, int $companyId): int
	{
		$this->database->query('INSERT INTO sites', [
			'url' => $url,
			'alias' => $alias,
			'shared_with' => $sharedWith ?: null,
			'key_companies' => $companyId,
			'added' => $this->dateTimeFactory->create(),
		]);
		return (int)$this->database->getInsertId();
	}


	public function generateId(?int $siteId, int $companyId): string
	{
		return $siteId !== null ? (string)$siteId : self::ALL . "-{$companyId}";
	}

}
