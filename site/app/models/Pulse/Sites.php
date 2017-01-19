<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

/**
 * Pulse sites service.
 *
 * @author Michal Špaček
 * @package pulse.michalspacek.cz
 */
class Sites
{

	/** @var string */
	public const ALL = 'all';

	/** @var \Nette\Database\Context */
	protected $database;


	/**
	 * @param \Nette\Database\Context $context
	 */
	public function __construct(\Nette\Database\Context $context)
	{
		$this->database = $context;
	}


	/**
	 * Get all sites.
	 *
	 * @return \Nette\Database\Row[]
	 */
	public function getAll(): array
	{
		return $this->database->fetchAll('SELECT id, url, alias FROM sites ORDER BY alias');
	}


	/**
	 * Get site by URL.
	 *
	 * @return \Nette\Database\Row|null
	 */
	public function getByUrl(string $url): ?\Nette\Database\Row
	{
		return $this->database->fetch('SELECT id, url, alias FROM sites WHERE url = ?', $url) ?: null;
	}


	/**
	 * Add site.
	 *
	 * @param string $url
	 * @param string $alias
	 * @param integer $companyId
	 * @return integer Id of newly inserted site
	 */
	public function add(string $url, string $alias, int $companyId): int
	{
		$this->database->query('INSERT INTO sites', ['url' => $url, 'alias' => $alias, 'key_companies' => $companyId]);
		return (int)$this->database->getInsertId();
	}

}
