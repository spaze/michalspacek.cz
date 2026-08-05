<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Encryption;

use InvalidArgumentException;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Database\ResultSet;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Spaze\Encryption\SymmetricKeyEncryption;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class ColumnReEncryptorTest extends TestCase
{

	private const string SELECT_SQL = 'SELECT ?name, ?name FROM ?name WHERE ?name IS NOT NULL AND ?name != ? AND ?name >= ? ORDER BY ?name LIMIT ?';
	private const string UPDATE_SQL = 'UPDATE ?name SET ?name = ? WHERE ?name = ? AND ?name = ?';
	private const int BATCH_SIZE = 1000;

	private readonly SymmetricKeyEncryption $encryption;
	private readonly SymmetricKeyEncryption $oldKeyEncryption;
	private readonly ColumnReEncryptor $reEncryptor;


	public function __construct(
		private readonly Database $database,
		private readonly TypedDatabase $typedDatabase,
		private readonly NullLogger $logger,
	) {
		// Both instances know both keys, so values written with the old key still decrypt; only the active key differs
		$keys = [
			'old' => 'mseetest_cafecafecafecafecafecafecafecafecafecafecafecafecafecafecafeb9a3',
			'new' => 'mseetest_cafecafecafecafecafecafecafecafecafecafecafecafecafecafecafef158',
		];
		$this->encryption = new SymmetricKeyEncryption($keys, 'new', 'mseetest');
		$this->oldKeyEncryption = new SymmetricKeyEncryption($keys, 'old', 'mseetest');
		$this->reEncryptor = new ColumnReEncryptor($this->database, $this->typedDatabase, self::BATCH_SIZE);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->logger->reset();
	}


	private static function column(SymmetricKeyEncryption $encryption): EncryptedColumn
	{
		return new EncryptedColumn($encryption, 'stuff', 'id_stuff', 'secret');
	}


	public function testReEncryptsValuesUsingAnOldKey(): void
	{
		$oldCiphertext1 = $this->oldKeyEncryption->encrypt('one@example.com');
		$currentCiphertext = $this->encryption->encrypt('two@example.com');
		$oldCiphertext2 = $this->oldKeyEncryption->encrypt('three@example.com');
		$this->database->setFetchPairsDefaultResult([
			5 => $oldCiphertext1,
			6 => $currentCiphertext,
			7 => $oldCiphertext2,
		]);
		$this->database->setResultSet(new ResultSet(1));

		$result = $this->reEncryptor->reEncrypt(self::column($this->encryption), false);

		Assert::same(2, $result->reEncrypted);
		Assert::same(1, $result->upToDate);
		Assert::same([], $result->failedIds);
		Assert::same(0, $result->changedMeanwhile);
		Assert::same(['id_stuff', 'secret', 'stuff', 'secret', 'secret', '', 'id_stuff', 0, 'id_stuff', self::BATCH_SIZE], $this->database->getParamsForQuery(self::SELECT_SQL));

		$params = $this->database->getParamsForQuery(self::UPDATE_SQL);
		Assert::count(14, $params); // 7 params per update, one update per old-key row, the current row is left alone

		$first = array_slice($params, 0, 7);
		Assert::same(['stuff', 'secret'], array_slice($first, 0, 2));
		assert(is_string($first[2]));
		Assert::false($this->encryption->needsReEncrypt($first[2])); // written back under the active key
		Assert::same('one@example.com', $this->encryption->decrypt($first[2])); // same plaintext, new ciphertext
		Assert::same(['id_stuff', 5, 'secret', $oldCiphertext1], array_slice($first, 3)); // the WHERE pins the exact value that was read

		$second = array_slice($params, 7, 7);
		assert(is_string($second[2]));
		Assert::same('three@example.com', $this->encryption->decrypt($second[2]));
		Assert::same(['id_stuff', 7, 'secret', $oldCiphertext2], array_slice($second, 3));
	}


	public function testDryRunDecryptsAndCountsButWritesNothing(): void
	{
		$this->database->setFetchPairsDefaultResult([
			5 => $this->oldKeyEncryption->encrypt('one@example.com'),
			6 => $this->encryption->encrypt('two@example.com'),
		]);

		$result = $this->reEncryptor->reEncrypt(self::column($this->encryption), true);

		Assert::same(1, $result->reEncrypted);
		Assert::same(1, $result->upToDate);
		Assert::same([], $result->failedIds);
		Assert::same(0, $result->changedMeanwhile);
		Assert::same([], $this->database->getParamsForQuery(self::UPDATE_SQL));
	}


	public function testDryRunReportsValuesItCouldNotDecrypt(): void
	{
		$this->database->setFetchPairsDefaultResult([
			1 => '$dropped$deadbeef', // the key id isn't the active one but can't be decrypted
			2 => $this->oldKeyEncryption->encrypt('one@example.com'),
		]);

		$result = $this->reEncryptor->reEncrypt(self::column($this->encryption), true);

		Assert::same(1, $result->reEncrypted);
		Assert::same(0, $result->upToDate);
		Assert::same([1], $result->failedIds);
		Assert::same(0, $result->changedMeanwhile);
		Assert::count(1, $this->logger->getLogged());
		Assert::same([], $this->database->getParamsForQuery(self::UPDATE_SQL)); // still writes nothing
	}


	public function testUndecryptableValuesAreSkippedAndTheRunContinues(): void
	{
		$oldCiphertext = $this->oldKeyEncryption->encrypt('still@example.com');
		$this->database->setFetchPairsDefaultResult([
			1 => 'not-a-valid-ciphertext',
			2 => '$dropped$deadbeef', // a key id no longer in the configuration
			3 => $oldCiphertext,
		]);
		$this->database->setResultSet(new ResultSet(1));

		$result = $this->reEncryptor->reEncrypt(self::column($this->encryption), false);

		Assert::same(1, $result->reEncrypted); // the value after the broken ones is still processed
		Assert::same(0, $result->upToDate);
		Assert::same([1, 2], $result->failedIds);
		Assert::same(0, $result->changedMeanwhile);
		Assert::count(2, $this->logger->getLogged()); // both broken values are logged for the operator
		Assert::count(7, $this->database->getParamsForQuery(self::UPDATE_SQL));
	}


	public function testRowChangedBetweenReadAndWriteIsLeftAlone(): void
	{
		$this->database->setFetchPairsDefaultResult([
			5 => $this->oldKeyEncryption->encrypt('one@example.com'),
		]);
		$this->database->setResultSet(new ResultSet(0)); // the UPDATE matched nothing, someone changed the row meanwhile

		$result = $this->reEncryptor->reEncrypt(self::column($this->encryption), false);

		Assert::same(0, $result->reEncrypted);
		Assert::same(0, $result->upToDate);
		Assert::same([], $result->failedIds);
		Assert::same(1, $result->changedMeanwhile);
	}


	public function testBatchSizeBelowOneIsRejected(): void
	{
		Assert::exception(function (): void {
			new ColumnReEncryptor($this->database, $this->typedDatabase, 0);
		}, InvalidArgumentException::class, 'Batch size must be at least 1, 0 given');
		Assert::exception(function (): void {
			new ColumnReEncryptor($this->database, $this->typedDatabase, -1);
		}, InvalidArgumentException::class, 'Batch size must be at least 1, -1 given');
	}


	public function testReadsFurtherBatchesUntilOneIsNotFull(): void
	{
		$reEncryptor = new ColumnReEncryptor($this->database, $this->typedDatabase, 2);
		$this->database->addFetchPairsResult([ // a full batch, so another one is read
			5 => $this->oldKeyEncryption->encrypt('one@example.com'),
			6 => 'not-a-valid-ciphertext', // a skipped value still moves the next batch past it
		]);
		$this->database->addFetchPairsResult([
			9 => $this->oldKeyEncryption->encrypt('three@example.com'),
		]);
		$this->database->setResultSet(new ResultSet(1));

		$result = $reEncryptor->reEncrypt(self::column($this->encryption), false);

		Assert::same(2, $result->reEncrypted); // rows from both batches
		Assert::same(0, $result->upToDate);
		Assert::same([6], $result->failedIds); // the skipped row is reported by its own id, not by its position
		Assert::same(0, $result->changedMeanwhile);

		$params = $this->database->getParamsForQuery(self::SELECT_SQL);
		Assert::count(20, $params); // 10 params per select, two selects
		Assert::same(0, $params[7]); // the first batch starts at the beginning, id 0 included
		Assert::same(7, $params[17]); // the second one starts just past the last row of the first, skipped or not
	}

}

TestCaseRunner::run(ColumnReEncryptorTest::class);
