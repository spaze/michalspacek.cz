<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use DateTime;
use Nette\Database\Explorer;
use Nette\Database\Row;

readonly class Companies
{

	public function __construct(
		private Explorer $database,
	) {
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
		return $this->database->fetch('SELECT id, name, alias FROM companies WHERE name = ?', $name);
	}


	/**
	 * Add company.
	 *
	 * @return int Id of newly inserted company
	 */
	public function add(string $name, string $tradeName, string $alias): int
	{
		$this->database->query('INSERT INTO companies', [
			'name' => $name,
			'trade_name' => (empty($tradeName) ? null : $tradeName),
			'alias' => $alias,
			'added' => new DateTime(),
		]);
		return (int)$this->database->getInsertId();
	}

}
