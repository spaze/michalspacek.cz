<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\DateTime\DateTimeFactory;
use Nette\Database\Explorer;

final readonly class Companies
{

	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
		private DateTimeFactory $dateTimeFactory,
	) {
	}


	/**
	 * @return list<Company>
	 */
	public function getAll(): array
	{
		$rows = $this->typedDatabase->fetchAll(
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
			assert(is_int($row->id));
			assert(is_string($row->name));
			assert(is_string($row->tradeName) || $row->tradeName === null);
			assert(is_string($row->alias));
			assert(is_string($row->sortName));
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
		if ($row === null) {
			return null;
		}
		assert(is_int($row->id));
		assert(is_string($row->name));
		assert($row->tradeName === null || is_string($row->tradeName));
		assert(is_string($row->alias));
		assert(is_string($row->sortName));

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
			'trade_name' => $tradeName === '' ? null : $tradeName,
			'alias' => $alias,
			'added' => $this->dateTimeFactory->create(),
		]);
		return (int)$this->database->getInsertId();
	}

}
