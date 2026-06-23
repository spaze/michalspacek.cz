<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
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


	public function testGetEmailNullWhenUnset(): void
	{
		$this->database->setFetchFieldDefaultResult(null);
		Assert::null($this->userAccounts->getEmail(42));
	}


	public function testEmailIsStoredEncryptedAndReadBackDecrypted(): void
	{
		$this->userAccounts->setEmail(42, 'me@example.com');

		$params = $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE id_user = ?');
		$stored = $params[0]['email'];
		assert(is_string($stored));
		Assert::notSame('me@example.com', $stored); // stored as ciphertext, never plaintext

		$this->database->setFetchFieldDefaultResult($stored);
		Assert::same('me@example.com', $this->userAccounts->getEmail(42)); // decrypts back to the original
	}

}

TestCaseRunner::run(UserAccountsTest::class);
