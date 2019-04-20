<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

class Companies
{

	/** @var \Nette\Database\Context */
	protected $database;


	public function __construct(\Nette\Database\Context $context)
	{
		$this->database = $context;
	}


	/**
	 * Get all companies.
	 *
	 * @return \Nette\Database\Row[] of [id, name]
	 */
	public function getAll(): array
	{
		return $this->database->fetchAll('SELECT id, name, alias FROM companies ORDER BY name');
	}


	public function getByName(string $name): ?\Nette\Database\Row
	{
		return $this->database->fetch('SELECT id, name, alias FROM companies WHERE name = ?', $name) ?: null;
	}


	/**
	 * Add company.
	 *
	 * @param string $name
	 * @param string $tradeName
	 * @param string $alias
	 * @return integer Id of newly inserted company
	 */
	public function add(string $name, string $tradeName, string $alias): int
	{
		$this->database->query('INSERT INTO companies', [
			'name' => $name,
			'trade_name' => (empty($tradeName) ? null : $tradeName),
			'alias' => $alias,
		]);
		return (int)$this->database->getInsertId();
	}

}
