<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use Nette\Database\Context;
use Nette\Database\Row;

class Companies
{

	/** @var Context */
	protected $database;


	public function __construct(Context $context)
	{
		$this->database = $context;
	}


	/**
	 * Get all companies.
	 *
	 * @return Row[]
	 */
	public function getAll(): array
	{
		return $this->database->fetchAll('SELECT id, name, alias FROM companies ORDER BY name');
	}


	public function getByName(string $name): ?Row
	{
		/** @var Row|null $result */
		$result = $this->database->fetch('SELECT id, name, alias FROM companies WHERE name = ?', $name);
		return $result;
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
