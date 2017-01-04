<?php
namespace MichalSpacekCz\Pulse;

/**
 * Pulse companies service.
 *
 * @author Michal Špaček
 * @package pulse.michalspacek.cz
 */
class Companies
{

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
	 * Get all companies.
	 *
	 * @return array of [id, name]
	 */
	public function getAll()
	{
		return $this->database->fetchAll('SELECT id, name, alias FROM companies ORDER BY name');
	}


	/**
	 * Get company by name.
	 *
	 * @return \Nette\Database\Row
	 */
	public function getByName($name)
	{
		return $this->database->fetch('SELECT id, name, alias FROM companies WHERE name = ?', $name);
	}


	/**
	 * Add company.
	 *
	 * @param string $name
	 * @param string $tradeName
	 * @param string $alias
	 * @return integer Id of newly inserted company
	 */
	public function add($name, $tradeName, $alias)
	{
		$this->database->query('INSERT INTO companies', [
			'name' => $name,
			'trade_name' => (empty($tradeName) ? null : $tradeName),
			'alias' => $alias,
		]);
		return (int)$this->database->getInsertId();
	}

}
