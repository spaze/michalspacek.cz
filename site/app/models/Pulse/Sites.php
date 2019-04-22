<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use Nette\Database\Context;
use Nette\Database\Row;

class Sites
{

	/** @var string */
	public const ALL = 'all';

	/** @var Context */
	protected $database;


	public function __construct(Context $context)
	{
		$this->database = $context;
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
		return $this->database->fetch('SELECT id, url, alias FROM sites WHERE url = ?', $url) ?: null;
	}


	/**
	 * Add site.
	 *
	 * @param string $url
	 * @param string $alias
	 * @param string $sharedWith
	 * @param integer $companyId
	 * @return integer Id of newly inserted site
	 */
	public function add(string $url, string $alias, string $sharedWith, int $companyId): int
	{
		$this->database->query('INSERT INTO sites', [
			'url' => $url,
			'alias' => $alias,
			'shared_with' => $sharedWith ?: null,
			'key_companies' => $companyId,
		]);
		return (int)$this->database->getInsertId();
	}

}
