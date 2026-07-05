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
	}


	public function testNotificationEmailIsStoredEncryptedAndReadBackDecrypted(): void
	{
		$this->userAccounts->setNotificationEmail(42, 'me@example.com');

		$params = $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE id_user = ?');
		$stored = $params[0]['notification_email'];
		assert(is_string($stored));
		Assert::notSame('me@example.com', $stored); // stored as ciphertext, never plaintext

		$this->database->setFetchFieldDefaultResult($stored);
		Assert::same('me@example.com', $this->userAccounts->getNotificationEmail(42)); // decrypts back to the original
	}


	public function testChangeNotificationEmailReturnsOldAndWritesNewInOneTransaction(): void
	{
		$this->userAccounts->setNotificationEmail(42, 'old@example.com');
		$encryptedOld = $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE id_user = ?')[0]['notification_email'];
		assert(is_string($encryptedOld));
		$this->database->reset();
		$this->database->setFetchFieldDefaultResult($encryptedOld); // the locked read returns the old ciphertext

		$old = $this->userAccounts->changeNotificationEmail(42, 'new@example.com');

		Assert::same('old@example.com', $old); // previous notification email, decrypted from the locked read
		Assert::same(DatabaseTransactionStatus::Committed, $this->database->transactionStatus); // read + write committed together
		$stored = $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE id_user = ?')[0]['notification_email'];
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
		Assert::same([], $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE id_user = ?')); // the new notification email was never written
	}

}

TestCaseRunner::run(UserAccountsTest::class);
