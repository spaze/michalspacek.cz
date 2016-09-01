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
		return $this->database->fetchAll('SELECT id, name FROM companies ORDER BY name');
	}


	/**
	 * Get company by name.
	 *
	 * @return array of [id, name]
	 */
	public function getByName($name)
	{
		return $this->database->fetch('SELECT id, name FROM companies WHERE name = ?', $name);
	}


	/**
	 * Add company.
	 *
	 * @param string $name
	 * @return integer Id of newly inserted company
	 */
	public function add($name)
	{
		$this->database->query('INSERT INTO companies', ['name' => $name]);
		return $this->database->getInsertId();
	}

}
