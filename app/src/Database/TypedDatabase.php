<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Database;

use JetBrains\PhpStorm\Language;
use MichalSpacekCz\Database\Exceptions\TypedDatabaseTypeException;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Utils\DateTime;

readonly class TypedDatabase
{

	public function __construct(
		private Explorer $database,
	) {
	}


	/**
	 * @param literal-string $sql
	 * @param array<array-key, mixed> $params
	 * @return array<string, string>
	 */
	public function fetchPairsStringString(#[Language('SQL')] string $sql, #[Language('GenericSQL')] ...$params): array
	{
		$values = [];
		/**
		 * @var mixed $value
		 */
		foreach ($this->database->fetchPairs($sql, ...$params) as $key => $value) {
			if (!is_string($key)) {
				throw new TypedDatabaseTypeException('string', $key);
			} elseif (!is_string($value)) {
				throw new TypedDatabaseTypeException('string', $value);
			}
			$values[$key] = $value;
		}
		return $values;
	}


	/**
	 * @param literal-string $sql
	 * @param array<array-key, mixed> $params
	 * @return array<int, string>
	 */
	public function fetchPairsIntString(#[Language('SQL')] string $sql, #[Language('GenericSQL')] ...$params): array
	{
		$values = [];
		/**
		 * @var mixed $value
		 */
		foreach ($this->database->fetchPairs($sql, ...$params) as $key => $value) {
			if (!is_int($key)) {
				throw new TypedDatabaseTypeException('int', $key);
			} elseif (!is_string($value)) {
				throw new TypedDatabaseTypeException('string', $value);
			}
			$values[$key] = $value;
		}
		return $values;
	}


	/**
	 * @param literal-string $sql
	 * @param array<array-key, mixed> $params
	 * @return list<DateTime>
	 */
	public function fetchPairsListDateTime(#[Language('SQL')] string $sql, #[Language('GenericSQL')] ...$params): array
	{
		$values = [];
		foreach ($this->database->fetchPairs($sql, ...$params) as $value) {
			if (!$value instanceof DateTime) {
				throw new TypedDatabaseTypeException(DateTime::class, $value);
			}
			$values[] = $value;
		}
		return $values;
	}


	/**
	 * @param literal-string $sql
	 * @param array<array-key, mixed> $params
	 */
	public function fetchFieldString(#[Language('SQL')] string $sql, #[Language('GenericSQL')] ...$params): string
	{
		$field = $this->database->fetchField($sql, ...$params);
		if (!is_string($field)) {
			throw new TypedDatabaseTypeException('string', $field);
		}
		return $field;
	}


	/**
	 * @param literal-string $sql
	 * @param array<array-key, mixed> $params
	 */
	public function fetchFieldStringNullable(#[Language('SQL')] string $sql, #[Language('GenericSQL')] ...$params): ?string
	{
		$field = $this->database->fetchField($sql, ...$params);
		if (!is_string($field) && !is_null($field)) {
			throw new TypedDatabaseTypeException('string|null', $field);
		}
		return $field;
	}


	/**
	 * @param literal-string $sql
	 * @param array<array-key, mixed> $params
	 */
	public function fetchFieldInt(#[Language('SQL')] string $sql, #[Language('GenericSQL')] ...$params): int
	{
		$field = $this->database->fetchField($sql, ...$params);
		if (!is_int($field)) {
			throw new TypedDatabaseTypeException('int', $field);
		}
		return $field;
	}


	/**
	 * @param literal-string $sql
	 * @param array<array-key, mixed> $params
	 */
	public function fetchFieldIntNullable(#[Language('SQL')] string $sql, #[Language('GenericSQL')] ...$params): ?int
	{
		$field = $this->database->fetchField($sql, ...$params);
		if (!is_int($field) && !is_null($field)) {
			throw new TypedDatabaseTypeException('int|null', $field);
		}
		return $field;
	}


	/**
	 * @param literal-string $sql
	 * @param array<array-key, mixed> $params
	 */
	public function fetchFieldDateTime(#[Language('SQL')] string $sql, #[Language('GenericSQL')] ...$params): DateTime
	{
		$field = $this->database->fetchField($sql, ...$params);
		if (!$field instanceof DateTime) {
			throw new TypedDatabaseTypeException(DateTime::class, $field);
		}
		return $field;
	}


	/**
	 * @param literal-string $sql
	 * @param array<array-key, mixed> $params
	 */
	public function fetchFieldDateTimeNullable(#[Language('SQL')] string $sql, #[Language('GenericSQL')] ...$params): ?DateTime
	{
		$field = $this->database->fetchField($sql, ...$params);
		if (!$field instanceof DateTime && !is_null($field)) {
			throw new TypedDatabaseTypeException(DateTime::class . '|null', $field);
		}
		return $field;
	}


	/**
	 * @param literal-string $sql
	 * @param array<array-key, mixed> $params
	 * @return array<array-key, Row>
	 */
	public function fetchAll(#[Language('SQL')] string $sql, #[Language('GenericSQL')] ...$params): array
	{
		$rows = $this->database->fetchAll($sql, ...$params);
		$result = [];
		foreach ($rows as $row) {
			if (!$row instanceof Row) {
				throw new TypedDatabaseTypeException(Row::class, $row);
			}
			$result[] = $row;
		}
		return $result;
	}

}
