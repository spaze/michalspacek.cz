<?php
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
	const ALL = 'all';

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
	 * @return array
	 */
	public function getAll()
	{
		return $this->database->fetchAll('SELECT id, url, alias FROM sites ORDER BY alias');
	}


	/**
	 * Get site by URL.
	 *
	 * @return array of [id, url, alias]
	 */
	public function getByUrl($url)
	{
		return $this->database->fetch('SELECT id, url, alias FROM sites WHERE url = ?', $url);
	}


	/**
	 * Add site.
	 *
	 * @param string $url
	 * @param string $alias
	 * @param string $companyId
	 * @return integer Id of newly inserted site
	 */
	public function add($url, $alias, $companyId)
	{
		$this->database->query('INSERT INTO sites', ['url' => $url, 'alias' => $alias, 'key_companies' => $companyId]);
		return $this->database->getInsertId();
	}

}
