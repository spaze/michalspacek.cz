<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Database;

use Nette\Database\Explorer;
use Nette\Database\Row;

class Database extends Explorer
{

	/** @var array<string, string> */
	private array $fetchPairsResult = [];

	/** @var array<int, Row> */
	private array $fetchAllResult = [];


	public function reset(): void
	{
		$this->fetchPairsResult = [];
		$this->fetchAllResult = [];
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
	 * @param array<int, array<string, string|null>> $fetchAllResult
	 * @return void
	 */
	public function setFetchAllResult(array $fetchAllResult): void
	{
		$this->fetchAllResult = [];
		foreach ($fetchAllResult as $row) {
			$this->fetchAllResult[] = Row::from($row);
		}
	}


	/**
	 * @param literal-string $sql
	 * @param string ...$params
	 * @return array<int, Row>
	 */
	public function fetchAll(string $sql, ...$params): array
	{
		return $this->fetchAllResult;
	}

}
