<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Database;

use Nette\Database\Explorer;
use Nette\Database\Row;

class Database extends Explorer
{

	/** @var array<string, string> */
	private array $fetchPairsResult = [];

	/** @var list<Row> */
	private array $fetchAllDefaultResult = [];

	/** @var list<list<Row>> */
	private array $fetchAllResults = [];

	private int $fetchAllResultsPosition = 0;


	public function reset(): void
	{
		$this->fetchPairsResult = [];
		$this->fetchAllDefaultResult = [];
		$this->fetchAllResults = [];
		$this->fetchAllResultsPosition = 0;
	}


	/**
	 * @param array<string, string> $fetchPairsResult
	 */
	public function setFetchPairsResult(array $fetchPairsResult): void
	{
		$this->fetchPairsResult = $fetchPairsResult;
	}


	/**
	 * @param literal-string $sql
	 * @param string ...$params
	 * @return array<string, string>
	 */
	public function fetchPairs(string $sql, ...$params): array
	{
		return $this->fetchPairsResult;
	}


	/**
	 * @param list<array<string, string|null>> $fetchAllDefaultResult
	 * @return void
	 */
	public function setFetchAllDefaultResult(array $fetchAllDefaultResult): void
	{
		$this->fetchAllDefaultResult = $this->getRows($fetchAllDefaultResult);
	}


	/**
	 * @param list<array<string, string|null>> $fetchAllResult
	 * @return void
	 */
	public function addFetchAllResult(array $fetchAllResult): void
	{
		$this->fetchAllResults[] = $this->getRows($fetchAllResult);
	}


	/**
	 * @param list<array<string, string|null>> $fetchAllResult
	 * @return list<Row>
	 */
	private function getRows(array $fetchAllResult): array
	{
		$result = [];
		foreach ($fetchAllResult as $row) {
			$result[] = Row::from($row);
		}
		return $result;
	}


	/**
	 * @param literal-string $sql
	 * @param string ...$params
	 * @return list<Row>
	 */
	public function fetchAll(string $sql, ...$params): array
	{
		return $this->fetchAllResults[$this->fetchAllResultsPosition++] ?? $this->fetchAllDefaultResult;
	}

}
