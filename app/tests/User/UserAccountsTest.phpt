<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Database\DatabaseTransactionStatus;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Spaze\Encryption\Exceptions\InvalidNumberOfComponentsException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class UserAccountsTest extends TestCase
{

	public function __construct(
		private readonly UserAccounts $userAccounts,
		private readonly Database $database,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGetNotificationEmailNullWhenUnset(): void
	{
		$this->database->setFetchFieldDefaultResult(null);
		Assert::null($this->userAccounts->getNotificationEmail(42));
		// Reads the row of the user that was asked for: without this, returning somebody else's address goes unnoticed
		Assert::same(['notification_email', 'users', 'id_user', 42], $this->database->getParamsForQuery('SELECT ?name FROM ?name WHERE ?name = ?'));
	}


	public function testNotificationEmailIsStoredEncryptedAndReadBackDecrypted(): void
	{
		$this->userAccounts->setNotificationEmail(42, 'me@example.com');

		$params = $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE ?name = ?');
		$stored = $params[0]['notification_email'];
		assert(is_string($stored));
		Assert::notSame('me@example.com', $stored); // stored as ciphertext, never plaintext

		$this->database->setFetchFieldDefaultResult($stored);
		Assert::same('me@example.com', $this->userAccounts->getNotificationEmail(42)); // decrypts back to the original
	}


	public function testChangeNotificationEmailReturnsOldAndWritesNewInOneTransaction(): void
	{
		$this->userAccounts->setNotificationEmail(42, 'old@example.com');
		$encryptedOld = $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE ?name = ?')[0]['notification_email'];
		assert(is_string($encryptedOld));
		$this->database->reset();
		$this->database->setFetchFieldDefaultResult($encryptedOld); // the locked read returns the old ciphertext

		$old = $this->userAccounts->changeNotificationEmail(42, 'new@example.com');

		Assert::same('old@example.com', $old); // previous notification email, decrypted from the locked read
		Assert::same(DatabaseTransactionStatus::Committed, $this->database->transactionStatus); // read + write committed together
		$stored = $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE ?name = ?')[0]['notification_email'];
		assert(is_string($stored));
		Assert::notSame('new@example.com', $stored); // new notification email stored encrypted
	}


	public function testChangeNotificationEmailRollsBackWhenOldValueCannotBeDecrypted(): void
	{
		$this->database->setFetchFieldDefaultResult('not-a-valid-ciphertext'); // the locked read returns an undecryptable old value

		Assert::exception(function (): void {
			$this->userAccounts->changeNotificationEmail(42, 'new@example.com');
		}, InvalidNumberOfComponentsException::class);

		// the old value is decrypted before the write, so a bad one aborts the transaction instead of committing the new notification email then throwing
		Assert::same(DatabaseTransactionStatus::RolledBack, $this->database->transactionStatus);
		Assert::same([], $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE ?name = ?')); // the new notification email was never written
	}


	public function testDeclaresTheNotificationEmailColumnWithTheKeyThatStoredIt(): void
	{
		Assert::same('user notification emails', $this->userAccounts->getEncryptedDataLabel());

		$columns = $this->userAccounts->getEncryptedColumns();
		Assert::count(1, $columns);
		Assert::same('users', $columns[0]->table);
		Assert::same('id_user', $columns[0]->idColumn);
		Assert::same('notification_email', $columns[0]->valueColumn);

		// The declared key set has to be the one the column was written with, or a sweep would find every row undecryptable
		$this->userAccounts->setNotificationEmail(42, 'me@example.com');
		$stored = $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE ?name = ?')[0]['notification_email'];
		assert(is_string($stored));
		Assert::same('me@example.com', $columns[0]->encryption->decrypt($stored));
	}

}

TestCaseRunner::run(UserAccountsTest::class);
