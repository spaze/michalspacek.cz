<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use DateTime;
use Nette\Database\Explorer;
use Nette\Database\Row;

readonly class Sites
{

	public const ALL = 'all';


	public function __construct(
		private Explorer $database,
	) {
	}


	/**
	 * Get all sites.
	 *
	 * @return Row[]
	 */
	public function getAll(): array
	{
		return $this->database->fetchAll('SELECT id, url, alias FROM sites ORDER BY alias');
	}


	public function getByUrl(string $url): ?Row
	{
		return $this->database->fetch('SELECT id, url, alias FROM sites WHERE url = ?', $url);
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
			'added' => new DateTime(),
		]);
		return (int)$this->database->getInsertId();
	}


	public function generateId(?int $siteId, int $companyId): string
	{
		return $siteId ? (string)$siteId : self::ALL . "-{$companyId}";
	}

}
