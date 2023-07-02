<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Database;

use DateTime;
use DateTimeInterface;
use MichalSpacekCz\Test\WillThrow;
use Nette\Database\Explorer;
use Nette\Database\Row;

class Database extends Explorer
{

	use WillThrow;


	private string $insertId = '';

	/** @var array<string, array<string|int, string|int|DateTimeInterface|null>> */
	private array $queries = [];

	/** @var array<string, int|string|null> */
	private array $fetchResult = [];

	/** @var mixed */
	private mixed $fetchFieldResult = null;

	/** @var array<string, string> */
	private array $fetchPairsResult = [];

	/** @var list<Row> */
	private array $fetchAllDefaultResult = [];

	/** @var list<list<Row>> */
	private array $fetchAllResults = [];

	private int $fetchAllResultsPosition = 0;


	public function reset(): void
	{
		$this->queries = [];
		$this->fetchResult = [];
		$this->fetchPairsResult = [];
		$this->fetchAllDefaultResult = [];
		$this->fetchAllResults = [];
		$this->fetchAllResultsPosition = 0;
		$this->wontThrow();
	}


	public function beginTransaction(): void
	{
	}


	public function commit(): void
	{
	}


	public function setInsertId(string $insertId): void
	{
		$this->insertId = $insertId;
	}


	public function getInsertId(?string $sequence = null): string
	{
		return $this->insertId;
	}


	/**
	 * @param literal-string $sql
	 * @param string|int|DateTimeInterface|null ...$params
	 * @return ResultSet
	 */
	public function query(string $sql, ...$params): ResultSet
	{
		$this->maybeThrow();
		$this->queries[$sql] = array_merge($this->queries[$sql] ?? [], $params);
		return new ResultSet();
	}


	/**
	 * @return array<string|int, string|int|DateTimeInterface|null>
	 */
	public function getParamsForQuery(string $query): array
	{
		return $this->queries[$query] ?? [];
	}


	/**
	 * @param array<string, int|string|bool|DateTime|null> $fetchResult
	 */
	public function setFetchResult(array $fetchResult): void
	{
		$this->fetchResult = $fetchResult;
	}


	/**
	 * @param literal-string $sql
	 * @param string ...$params
	 */
	public function fetch(string $sql, ...$params): ?Row
	{
		$row = Row::from($this->fetchResult);
		return $row->count() > 0 ? $row : null;
	}


	public function setFetchFieldResult(mixed $fetchFieldResult): void
	{
		$this->fetchFieldResult = $fetchFieldResult;
	}


	/**
	 * @param literal-string $sql
	 * @param string ...$params
	 */
	public function fetchField(string $sql, ...$params): mixed
	{
		return $this->fetchFieldResult;
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
	 * @param list<array<string, int|string|bool|DateTime|null>> $fetchAllDefaultResult
	 * @return void
	 */
	public function setFetchAllDefaultResult(array $fetchAllDefaultResult): void
	{
		$this->fetchAllDefaultResult = $this->getRows($fetchAllDefaultResult);
	}


	/**
	 * @param list<array<string, int|string|bool|DateTime|null>> $fetchAllResult
	 * @return void
	 */
	public function addFetchAllResult(array $fetchAllResult): void
	{
		$this->fetchAllResults[] = $this->getRows($fetchAllResult);
	}


	/**
	 * @param list<array<string, int|string|bool|DateTime|null>> $fetchAllResult
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
