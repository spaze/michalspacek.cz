<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use DateTime;
use Nette\Database\Explorer;

readonly class Companies
{

	public function __construct(
		private Explorer $database,
	) {
	}


	/**
	 * @return list<Company>
	 */
	public function getAll(): array
	{
		$rows = $this->database->fetchAll(
			'SELECT
				id,
				name,
				trade_name AS tradeName,
				alias,
				COALESCE(trade_name, name) AS sortName
			FROM companies
			ORDER BY name',
		);
		$companies = [];
		foreach ($rows as $row) {
			$companies[] = new Company($row->id, $row->name, $row->tradeName, $row->alias, $row->sortName);
		}
		return $companies;
	}


	public function getByName(string $name): ?Company
	{
		$row = $this->database->fetch(
			'SELECT
				id,
				name,
				trade_name AS tradeName,
				alias,
				COALESCE(trade_name, name) AS sortName
				FROM companies
				WHERE name = ?',
			$name,
		);
		if (!$row) {
			return null;
		}
		return new Company($row->id, $row->name, $row->tradeName, $row->alias, $row->sortName);
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
