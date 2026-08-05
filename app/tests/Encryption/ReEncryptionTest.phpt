<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Encryption;

use MichalSpacekCz\Application\Cli\CliArgs;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Database\ResultSet;
use MichalSpacekCz\Test\Encryption\EncryptedStorageMock;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Database\DriverException;
use Override;
use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;
use Spaze\Encryption\SymmetricKeyEncryption;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class ReEncryptionTest extends TestCase
{

	private const string SELECT_SQL = 'SELECT ?name, ?name FROM ?name WHERE ?name IS NOT NULL AND ?name != ? AND ?name >= ? ORDER BY ?name LIMIT ?';
	private const string UPDATE_SQL = 'UPDATE ?name SET ?name = ? WHERE ?name = ? AND ?name = ?';

	private readonly SymmetricKeyEncryption $encryption;
	private readonly SymmetricKeyEncryption $oldKeyEncryption;
	private readonly ColumnReEncryptor $columnReEncryptor;


	public function __construct(
		private readonly Database $database,
		TypedDatabase $typedDatabase,
		private readonly NullLogger $logger,
	) {
		$keys = [
			'old' => 'mseetest_cafecafecafecafecafecafecafecafecafecafecafecafecafecafecafeb9a3',
			'new' => 'mseetest_cafecafecafecafecafecafecafecafecafecafecafecafecafecafecafef158',
		];
		$this->encryption = new SymmetricKeyEncryption($keys, 'new', 'mseetest');
		$this->oldKeyEncryption = new SymmetricKeyEncryption($keys, 'old', 'mseetest');
		$this->columnReEncryptor = new ColumnReEncryptor($this->database, $typedDatabase, 1000);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->logger->reset();
	}


	/**
	 * @param list<EncryptedStorage> $storages
	 * @return array{0: int, 1: string} The return code and the printed report
	 */
	private function runReEncryption(array $storages, ?CliArgs $cliArgs = null): array
	{
		$reEncryption = new ReEncryption($storages, $this->columnReEncryptor, $cliArgs ?? new CliArgs([], null), new ConsoleColor());
		ob_start();
		$code = $reEncryption->run();
		$output = ob_get_clean();
		assert(is_string($output));
		return [$code, $output];
	}


	/**
	 * @param non-empty-string $label
	 */
	private function storage(string $label, string $table, string $idColumn, string $valueColumn, string ...$moreValueColumns): EncryptedStorageMock
	{
		$columns = [new EncryptedColumn($this->encryption, $table, $idColumn, $valueColumn)];
		foreach ($moreValueColumns as $moreValueColumn) {
			$columns[] = new EncryptedColumn($this->encryption, $table, $idColumn, $moreValueColumn);
		}
		return new EncryptedStorageMock($label, $columns);
	}


	public function testReportsEveryStorageAndReturnsZeroWhenNothingFailed(): void
	{
		$this->database->addFetchPairsResult([1 => $this->oldKeyEncryption->encrypt('one@example.com')]);
		$this->database->addFetchPairsResult([2 => $this->encryption->encrypt('two@example.com')]);
		$this->database->setResultSet(new ResultSet(1));

		[$code, $output] = $this->runReEncryption([
			$this->storage('first storage', 'first_table', 'id_first', 'secret'),
			$this->storage('second storage', 'second_table', 'id_second', 'secret'),
		]);

		Assert::same(0, $code);
		Assert::same(
			"first storage: 1 re-encrypted, 0 up to date, 0 failed, 0 changed meanwhile\n"
			. "second storage: 0 re-encrypted, 1 up to date, 0 failed, 0 changed meanwhile\n",
			$output,
		);
	}


	public function testDryRunSaysWhatItWouldDoAndWritesNothing(): void
	{
		$this->database->setFetchPairsDefaultResult([1 => $this->oldKeyEncryption->encrypt('one@example.com')]);

		[$code, $output] = $this->runReEncryption(
			[$this->storage('some storage', 'some_table', 'id_some', 'secret')],
			new CliArgs(['--dry-run' => true], null),
		);

		Assert::same(0, $code);
		Assert::same("some storage: 1 would be re-encrypted, 0 up to date, 0 failed\n", $output);
		Assert::same([], $this->database->getParamsForQuery(self::UPDATE_SQL));
	}


	public function testValuesThatCannotBeDecryptedFailTheRunAndSayWhereTheyAre(): void
	{
		$this->database->setFetchPairsDefaultResult([41 => 'not-a-valid-ciphertext', 88 => '$dropped$deadbeef']);

		[$code, $output] = $this->runReEncryption([$this->storage('some storage', 'some_table', 'id_some', 'secret')]);

		Assert::same(1, $code);
		Assert::contains('2 failed (`some_table.id_some IN (41, 88)`)', $output); // ready to paste into a query
		Assert::count(2, $this->logger->getLogged());
	}


	public function testSingleFailureIsWrittenAsOneEquality(): void
	{
		$this->database->setFetchPairsDefaultResult([12 => 'not-a-valid-ciphertext']);

		[, $output] = $this->runReEncryption([$this->storage('some storage', 'some_table', 'id_some', 'secret')]);

		Assert::contains('1 failed (`some_table.id_some = 12`)', $output);
	}


	public function testStorageWithSeveralColumnsGetsALinePerColumn(): void
	{
		$this->database->addFetchPairsResult([7 => 'not-a-valid-ciphertext']);
		$this->database->addFetchPairsResult([8 => $this->encryption->encrypt('a note')]);

		[$code, $output] = $this->runReEncryption([$this->storage('some storage', 'some_table', 'id_some', 'email', 'note')]);

		Assert::same(1, $code);
		// Named per column, or the counts and the failed rows couldn't be told apart
		Assert::same(
			"some storage, email: 0 re-encrypted, 0 up to date, 1 failed (`some_table.id_some = 7`), 0 changed meanwhile\n"
			. "some storage, note: 0 re-encrypted, 1 up to date, 0 failed, 0 changed meanwhile\n",
			$output,
		);
		// Both columns were really read: the labels above come from the loop, so they'd look the same if one of them was swept twice
		$params = $this->database->getParamsForQuery(self::SELECT_SQL);
		Assert::contains('email', $params);
		Assert::contains('note', $params);
	}


	public function testColumnThatCannotBeWrittenIsReportedAndTheRestStillRuns(): void
	{
		$this->database->addFetchPairsResult([1 => $this->oldKeyEncryption->encrypt('one@example.com')]); // needs a write, which fails
		$this->database->addFetchPairsResult([2 => $this->encryption->encrypt('two@example.com')]); // needs nothing
		$exception = new DriverException('The database fell over');
		$this->database->willThrowOnce($exception);

		[$code, $output] = $this->runReEncryption([
			$this->storage('broken storage', 'broken_table', 'id_broken', 'secret'),
			$this->storage('working storage', 'working_table', 'id_working', 'secret'),
		]);

		Assert::same(1, $code);
		Assert::contains('working storage: 0 re-encrypted, 1 up to date, 0 failed, 0 changed meanwhile', $output);
		Assert::notContains('broken storage', $output); // failures go to the error output, so they survive `> report.txt`
		Assert::same([$exception], $this->logger->getLogged()); // the whole exception, a flattened message would lose the trace and the cause
	}


	public function testBadArgsAreReportedWithoutSweepingAnything(): void
	{
		[$code, $output] = $this->runReEncryption(
			[$this->storage('some storage', 'some_table', 'id_some', 'secret')],
			new CliArgs([], 'Unknown option --frobnicate'),
		);

		Assert::same(3, $code);
		Assert::notContains('Unknown option --frobnicate', $output); // the error output again, not the report
		Assert::same([], $this->database->getParamsForQuery(self::SELECT_SQL)); // nothing was even read
	}

}

TestCaseRunner::run(ReEncryptionTest::class);
