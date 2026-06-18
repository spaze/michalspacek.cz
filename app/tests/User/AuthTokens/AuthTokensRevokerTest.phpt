<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\AuthTokens;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class AuthTokensRevokerTest extends TestCase
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


	public function testRevokeForUserDeletesEveryTokenTypeForTheUser(): void
	{
		(new AuthTokensRevoker(new UserAuthTokens($this->database, 'users')))->revokeForUser(1337);

		// No type in the params: the delete is not scoped to a token type, so a future type is swept too
		Assert::same(
			[1337],
			$this->database->getParamsForQuery('DELETE FROM auth_tokens WHERE key_user = ?'),
		);
	}

}

TestCaseRunner::run(AuthTokensRevokerTest::class);
