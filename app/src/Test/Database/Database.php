<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Database;

use DateTime;
use DateTimeInterface;
use MichalSpacekCz\DateTime\DateTimeFormat;
use MichalSpacekCz\Test\WillThrow;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Override;

final class Database extends Explorer
{

	use WillThrow;


	private string $defaultInsertId = '';

	/** @var list<string> */
	private array $insertIds = [];

	private int $insertIdsPosition = 0;

	/** @var array<string, array<string|int, string|int|float|bool|null>> */
	private array $queriesScalarParams = [];

	/** @var array<string, array<int, array<string, string|int|float|bool|null>>> */
	private array $queriesArrayParams = [];

	/** @var array<string, int|string|DateTime|null> */
	private array $fetchDefaultResult = [];

	/** @var list<array<string, int|string|DateTime|null>> */
	private array $fetchResults = [];

	private int $fetchResultsPosition = 0;

	private mixed $fetchFieldDefaultResult = null;

	/** @var list<mixed> */
	private array $fetchFieldResults = [];

	private int $fetchFieldResultsPosition = 0;

	/** @var array<int|string, string|int|DateTimeInterface> */
	private array $fetchPairsDefaultResult = [];

	/** @var list<array<int|string, string|int|DateTimeInterface>> */
	private array $fetchPairsResults = [];

	private int $fetchPairsResultsPosition = 0;

	/** @var list<Row> */
	private array $fetchAllDefaultResult = [];

	/** @var list<list<Row>> */
	private array $fetchAllResults = [];

	private int $fetchAllResultsPosition = 0;

	private ?ResultSet $resultSet = null;


	public function reset(): void
	{
		$this->defaultInsertId = '';
		$this->insertIds = [];
		$this->insertIdsPosition = 0;
		$this->queriesScalarParams = [];
		$this->queriesArrayParams = [];
		$this->fetchDefaultResult = [];
		$this->fetchResults = [];
		$this->fetchResultsPosition = 0;
		$this->fetchPairsDefaultResult = [];
		$this->fetchPairsResults = [];
		$this->fetchPairsResultsPosition = 0;
		$this->fetchFieldDefaultResult = null;
		$this->fetchFieldResults = [];
		$this->fetchFieldResultsPosition = 0;
		$this->fetchAllDefaultResult = [];
		$this->fetchAllResults = [];
		$this->fetchAllResultsPosition = 0;
		$this->resultSet = null;
		$this->wontThrow();
	}


	#[Override]
	public function beginTransaction(): void
	{
	}


	#[Override]
	public function commit(): void
	{
	}


	#[Override]
	public function rollBack(): void
	{
	}


	public function setDefaultInsertId(string $insertId): void
	{
		$this->defaultInsertId = $insertId;
	}


	public function addInsertId(string $insertId): void
	{
		$this->insertIds[] = $insertId;
	}


	#[Override]
	public function getInsertId(?string $sequence = null): string
	{
		return $this->insertIds[$this->insertIdsPosition++] ?? $this->defaultInsertId;
	}


	/**
	 * @param literal-string $sql
	 * @param string|int|bool|DateTimeInterface|null|array<string, string|int|bool|DateTimeInterface|null> ...$params
	 * @return ResultSet
	 */
	#[Override]
	public function query(string $sql, ...$params): ResultSet
	{
		$this->maybeThrow();
		foreach ($params as $param) {
			if (is_array($param)) {
				$arrayParams = [];
				foreach ($param as $key => $value) {
					$arrayParams[$key] = $this->formatValue($value);
				}
				$this->queriesArrayParams[$sql][] = $arrayParams;
			} else {
				$this->queriesScalarParams[$sql][] = $this->formatValue($param);
			}
		}
		return $this->resultSet ?? new ResultSet();
	}


	/**
	 * Emulate how values are stored in database.
	 *
	 * For example datetime is stored without timezone info.
	 * The DateTime format here is the same as in \Nette\Database\Drivers\MySqlDriver::formatDateTime() but without the quotes.
	 */
	private function formatValue(string|int|float|bool|DateTimeInterface|null $value): string|int|float|bool|null
	{
		return $value instanceof DateTimeInterface ? $value->format(DateTimeFormat::MYSQL) : $value;
	}


	/**
	 * @return array<string|int, string|int|float|bool|DateTimeInterface|null>
	 */
	public function getParamsForQuery(string $query): array
	{
		return $this->queriesScalarParams[$query] ?? [];
	}


	/**
	 * @return array<int, array<string, string|int|float|bool|DateTimeInterface|null>>
	 */
	public function getParamsArrayForQuery(string $query): array
	{
		return $this->queriesArrayParams[$query] ?? [];
	}


	/**
	 * @param array<string, int|string|DateTime|null> $fetchDefaultResult
	 */
	public function setFetchDefaultResult(array $fetchDefaultResult): void
	{
		$this->fetchDefaultResult = $fetchDefaultResult;
	}


	/**
	 * @param array<string, int|string|DateTime|null> $fetchResult
	 */
	public function addFetchResult(array $fetchResult): void
	{
		$this->fetchResults[] = $fetchResult;
	}


	/**
	 * @param literal-string $sql
	 * @param string ...$params
	 */
	#[Override]
	public function fetch(string $sql, ...$params): ?Row
	{
		$row = $this->createRow($this->fetchResults[$this->fetchResultsPosition++] ?? $this->fetchDefaultResult);
		return $row->count() > 0 ? $row : null;
	}


	public function setFetchFieldDefaultResult(mixed $fetchFieldDefaultResult): void
	{
		$this->fetchFieldDefaultResult = $fetchFieldDefaultResult;
	}


	public function addFetchFieldResult(mixed $fetchFieldResult): void
	{
		$this->fetchFieldResults[] = $fetchFieldResult;
	}


	/**
	 * @param literal-string $sql
	 * @param string ...$params
	 */
	#[Override]
	public function fetchField(string $sql, ...$params): mixed
	{
		return $this->fetchFieldResults[$this->fetchFieldResultsPosition++] ?? $this->fetchFieldDefaultResult;
	}


	/**
	 * @param array<int|string, string|int|DateTimeInterface> $fetchPairsDefaultResult
	 */
	public function setFetchPairsDefaultResult(array $fetchPairsDefaultResult): void
	{
		$this->fetchPairsDefaultResult = $fetchPairsDefaultResult;
	}


	/**
	 * @param array<int|string, string|int|DateTimeInterface> $fetchPairsResult
	 */
	public function addFetchPairsResult(array $fetchPairsResult): void
	{
		$this->fetchPairsResults[] = $fetchPairsResult;
	}


	/**
	 * @param literal-string $sql
	 * @param string ...$params
	 * @return array<int|string, string|int|DateTimeInterface>
	 */
	#[Override]
	public function fetchPairs(string $sql, ...$params): array
	{
		return $this->fetchPairsResults[$this->fetchPairsResultsPosition++] ?? $this->fetchPairsDefaultResult;
	}


	/**
	 * @param list<array<string, int|string|DateTime|null>> $fetchAllDefaultResult
	 * @return void
	 */
	public function setFetchAllDefaultResult(array $fetchAllDefaultResult): void
	{
		$this->fetchAllDefaultResult = $this->getRows($fetchAllDefaultResult);
	}


	/**
	 * @param list<array<string, int|string|DateTime|null>> $fetchAllResult
	 * @return void
	 */
	public function addFetchAllResult(array $fetchAllResult): void
	{
		$this->fetchAllResults[] = $this->getRows($fetchAllResult);
	}


	public function setResultSet(ResultSet $resultSet): void
	{
		$this->resultSet = $resultSet;
	}


	/**
	 * @param list<array<string, int|string|DateTime|null>> $fetchAllResult
	 * @return list<Row>
	 */
	private function getRows(array $fetchAllResult): array
	{
		$result = [];
		foreach ($fetchAllResult as $row) {
			$result[] = $this->createRow($row);
		}
		return $result;
	}


	/**
	 * @param literal-string $sql
	 * @param string ...$params
	 * @return list<Row>
	 */
	#[Override]
	public function fetchAll(string $sql, ...$params): array
	{
		return $this->fetchAllResults[$this->fetchAllResultsPosition++] ?? $this->fetchAllDefaultResult;
	}


	/**
	 * Almost the same as Row::from() but with better/simpler types.
	 *
	 * @param array<string, int|string|DateTime|null> $array
	 */
	private function createRow(array $array): Row
	{
		$row = new Row();
		foreach ($array as $key => $value) {
			$row->$key = $value;
		}
		return $row;
	}

}
