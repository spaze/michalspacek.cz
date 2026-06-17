<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\AuthTokens;

use DateTimeImmutable;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Database\DatabaseTransactionStatus;
use MichalSpacekCz\Test\Database\ResultSet;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class UserAuthTokensTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testReplaceForUserDeletesThenInsertsInTransaction(): void
	{
		$userId = 1337;
		$value = (new UserAuthTokens($this->database, 'users'))
			->replaceForUser($userId, UserAuthTokenType::AdminPasskeyReset);

		Assert::same(DatabaseTransactionStatus::Committed, $this->database->transactionStatus);

		Assert::same(
			[$userId, UserAuthTokenType::AdminPasskeyReset->value],
			$this->database->getParamsForQuery('DELETE FROM auth_tokens WHERE key_user = ? AND type = ?'),
		);

		$inserts = $this->database->getParamsArrayForQuery('INSERT INTO auth_tokens');
		Assert::count(1, $inserts);
		Assert::same($userId, $inserts[0]['key_user']);
		Assert::same(UserAuthTokenType::AdminPasskeyReset->value, $inserts[0]['type']);
		Assert::type('string', $inserts[0]['selector']);
		Assert::type('string', $inserts[0]['token']);

		[$selector, $token] = explode(':', $value);
		Assert::same($inserts[0]['selector'], $selector);
		Assert::same($inserts[0]['token'], hash('sha512', $token));
	}


	public function testDeleteExpiredByType(): void
	{
		$this->database->setResultSet(new ResultSet(7));
		$before = new DateTimeImmutable('2026-05-10 00:00:00');
		$deleted = (new UserAuthTokens($this->database, 'users'))
			->deleteExpiredByType(UserAuthTokenType::PermanentLogin, $before);

		Assert::same(7, $deleted);
		Assert::same(
			[UserAuthTokenType::PermanentLogin->value, $before->format('Y-m-d H:i:s')],
			$this->database->getParamsForQuery('DELETE FROM auth_tokens WHERE type = ? AND created <= ?'),
		);
	}


	public function testDeleteAllTypesForUser(): void
	{
		(new UserAuthTokens($this->database, 'users'))->deleteAllTypesForUser(1337);

		// No type predicate, so every token type for the user is deleted
		Assert::same(
			[1337],
			$this->database->getParamsForQuery('DELETE FROM auth_tokens WHERE key_user = ?'),
		);
	}

}

TestCaseRunner::run(UserAuthTokensTest::class);
