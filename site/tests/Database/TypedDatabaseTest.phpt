<?php
declare(strict_types = 1);

namespace Database;

use MichalSpacekCz\Database\Exceptions\TypedDatabaseTypeException;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\DateTime\DateTime;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\DateTime as NetteDateTime;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TypedDatabaseTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TypedDatabase $typedDatabase,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testFetchPairsStringString(): void
	{
		$this->database->setFetchPairsResult([
			'foo' => 'bar',
			'waldo' => 'quux',
			'xyzzy' => 'fred',
		]);
		$list = $this->typedDatabase->fetchPairsStringString('SELECT foo');
		Assert::same('bar', $list['foo']);
		Assert::same('quux', $list['waldo']);
		Assert::same('fred', $list['xyzzy']);
	}


	public function testFetchPairsStringStringInvalidTypeKey(): void
	{
		$this->database->setFetchPairsResult([
			3 => 'foo',
		]);
		Assert::exception(function (): void {
			$this->typedDatabase->fetchPairsStringString('SELECT foo');
		}, TypedDatabaseTypeException::class, 'string expected, int given');
	}


	public function testFetchPairsStringStringInvalidTypeValue(): void
	{
		$this->database->setFetchPairsResult([
			'foo' => 3,
		]);
		Assert::exception(function (): void {
			$this->typedDatabase->fetchPairsStringString('SELECT foo');
		}, TypedDatabaseTypeException::class, 'string expected, int given');
	}


	public function testFetchPairsIntString(): void
	{
		$this->database->setFetchPairsResult([
			1 => 'bar',
			3 => 'quux',
			5 => 'fred',
		]);
		$list = $this->typedDatabase->fetchPairsIntString('SELECT foo');
		Assert::same('bar', $list[1]);
		Assert::same('quux', $list[3]);
		Assert::same('fred', $list[5]);
	}


	public function testFetchPairsIntStringInvalidTypeKey(): void
	{
		$this->database->setFetchPairsResult([
			'foo' => 'foo',
		]);
		Assert::exception(function (): void {
			$this->typedDatabase->fetchPairsIntString('SELECT foo');
		}, TypedDatabaseTypeException::class, 'int expected, string given');
	}


	public function testFetchPairsIntStringInvalidTypeValue(): void
	{
		$this->database->setFetchPairsResult([
			303 => 808,
		]);
		Assert::exception(function (): void {
			$this->typedDatabase->fetchPairsIntString('SELECT foo');
		}, TypedDatabaseTypeException::class, 'string expected, int given');
	}


	public function testFetchPairsListDateTime(): void
	{
		$this->database->setFetchPairsResult([
			1 => new NetteDateTime('2023-01-01 10:20:30'),
			3 => new NetteDateTime('2023-01-03 10:20:30'),
			5 => new NetteDateTime('2023-01-05 10:20:30'),
		]);
		$list = $this->typedDatabase->fetchPairsListDateTime('SELECT foo');
		Assert::type('list', $list);
		Assert::same('2023-01-01 10:20:30', $list[0]->format(DateTime::DATE_MYSQL));
		Assert::same('2023-01-03 10:20:30', $list[1]->format(DateTime::DATE_MYSQL));
		Assert::same('2023-01-05 10:20:30', $list[2]->format(DateTime::DATE_MYSQL));
	}


	public function testFetchPairsListDateTimeInvalidType(): void
	{
		$this->database->setFetchPairsResult([
			1 => new NetteDateTime('2023-01-01 10:20:30'),
			3 => 'foo',
		]);
		Assert::exception(function (): void {
			$this->typedDatabase->fetchPairsListDateTime('SELECT foo');
		}, TypedDatabaseTypeException::class, 'Nette\Utils\DateTime expected, string given');
	}


	public function testFetchFieldString(): void
	{
		$this->database->setFetchFieldDefaultResult('foo');
		Assert::same('foo', $this->typedDatabase->fetchFieldString('SELECT foo'));
	}


	public function testFetchFieldStringNullable(): void
	{
		$this->database->setFetchFieldDefaultResult('foo');
		Assert::same('foo', $this->typedDatabase->fetchFieldStringNullable('SELECT foo'));
		$this->database->setFetchFieldDefaultResult(null);
		Assert::null($this->typedDatabase->fetchFieldStringNullable('SELECT foo'));
	}


	public function testFetchFieldStringInvalidType(): void
	{
		$this->database->setFetchFieldDefaultResult(303);
		Assert::exception(function (): void {
			$this->typedDatabase->fetchFieldString('SELECT foo');
		}, TypedDatabaseTypeException::class, 'string expected, int given');
	}


	public function testFetchFieldStringNullableInvalidType(): void
	{
		$this->database->setFetchFieldDefaultResult(303);
		Assert::exception(function (): void {
			$this->typedDatabase->fetchFieldStringNullable('SELECT foo');
		}, TypedDatabaseTypeException::class, 'string|null expected, int given');
	}


	public function testFetchFieldInt(): void
	{
		$this->database->setFetchFieldDefaultResult(303);
		Assert::same(303, $this->typedDatabase->fetchFieldInt('SELECT 303'));
	}


	public function testFetchFieldIntNullable(): void
	{
		$this->database->setFetchFieldDefaultResult(303);
		Assert::same(303, $this->typedDatabase->fetchFieldIntNullable('SELECT 303'));
		$this->database->setFetchFieldDefaultResult(null);
		Assert::null($this->typedDatabase->fetchFieldIntNullable('SELECT 303'));
	}


	public function testFetchFieldIntInvalidType(): void
	{
		$this->database->setFetchFieldDefaultResult('foo');
		Assert::exception(function (): void {
			$this->typedDatabase->fetchFieldInt('SELECT 303');
		}, TypedDatabaseTypeException::class, 'int expected, string given');
	}


	public function testFetchFieldIntNullableInvalidType(): void
	{
		$this->database->setFetchFieldDefaultResult('foo');
		Assert::exception(function (): void {
			$this->typedDatabase->fetchFieldIntNullable('SELECT 303');
		}, TypedDatabaseTypeException::class, 'int|null expected, string given');
	}


	public function testFetchFieldDateTime(): void
	{
		$this->database->setFetchFieldDefaultResult(new NetteDateTime());
		Assert::type(NetteDateTime::class, $this->typedDatabase->fetchFieldDateTime('SELECT 808'));
	}


	public function testFetchFieldDateTimeNullable(): void
	{
		$this->database->setFetchFieldDefaultResult(new NetteDateTime());
		Assert::type(NetteDateTime::class, $this->typedDatabase->fetchFieldDateTimeNullable('SELECT 808'));
		$this->database->setFetchFieldDefaultResult(null);
		Assert::null($this->typedDatabase->fetchFieldIntNullable('SELECT 808'));
	}


	public function testFetchFieldDateTimeInvalidType(): void
	{
		$this->database->setFetchFieldDefaultResult('foo');
		Assert::exception(function (): void {
			$this->typedDatabase->fetchFieldDateTime('SELECT 808');
		}, TypedDatabaseTypeException::class, NetteDateTime::class . ' expected, string given');
	}


	public function testFetchFieldDateTimeNullableInvalidType(): void
	{
		$this->database->setFetchFieldDefaultResult('foo');
		Assert::exception(function (): void {
			$this->typedDatabase->fetchFieldDateTimeNullable('SELECT 808');
		}, TypedDatabaseTypeException::class, NetteDateTime::class . '|null expected, string given');
	}

}

TestCaseRunner::run(TypedDatabaseTest::class);
