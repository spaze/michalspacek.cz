<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\AuthTokens\UserAuthTokens;
use MichalSpacekCz\User\AuthTokens\UserAuthTokenType;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetDisabledException;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasskeyResetTokensTest extends TestCase
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


	public function testIsEnabled(): void
	{
		Assert::false($this->getTokens(false)->isEnabled());
		Assert::true($this->getTokens(true)->isEnabled());
		Assert::false($this->getTokens(false)->isEnabled());
	}


	public function testCreateThrowsWhenDisabled(): void
	{
		Assert::exception(function (): void {
			$this->getTokens(false)->create(1337);
		}, PasskeyResetDisabledException::class);
	}


	public function testVerifyThrowsWhenDisabled(): void
	{
		Assert::exception(function (): void {
			$this->getTokens(false)->verify('some token');
		}, PasskeyResetDisabledException::class);
	}


	public function testDeleteByIdQueriesAdminResetType(): void
	{
		$this->getTokens(true)->deleteById(42);
		Assert::same(
			[42, UserAuthTokenType::AdminPasskeyReset->value],
			$this->database->getParamsForQuery('DELETE FROM auth_tokens WHERE id_auth_token = ? AND type = ?'),
		);
	}


	private function getTokens(bool $enabled): PasskeyResetTokens
	{
		return new PasskeyResetTokens(new UserAuthTokens($this->database, 'users'), $enabled);
	}

}

TestCaseRunner::run(PasskeyResetTokensTest::class);
