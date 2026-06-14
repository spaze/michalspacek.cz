<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Database\ResultSet;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\AuthTokens\UserAuthTokens;
use MichalSpacekCz\User\AuthTokens\UserAuthTokenType;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationDisabledException;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasskeyResetTokensTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly DateTimeFactory $dateTimeFactory,
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
		}, PasskeyRegistrationDisabledException::class);
	}


	public function testVerifyThrowsWhenDisabled(): void
	{
		Assert::exception(function (): void {
			$this->getTokens(false)->verify('some token');
		}, PasskeyRegistrationDisabledException::class);
	}


	public function testDeleteByIdQueriesAdminResetType(): void
	{
		$this->getTokens(true)->deleteById(42);
		Assert::same(
			[42, UserAuthTokenType::AdminPasskeyReset->value],
			$this->database->getParamsForQuery('DELETE FROM auth_tokens WHERE id_auth_token = ? AND type = ?'),
		);
	}


	public function testDeleteExpiredQueriesAdminResetType(): void
	{
		$this->database->setResultSet(new ResultSet(2));
		$deleted = $this->getTokens(true)->deleteExpired();

		Assert::same(2, $deleted);
		$params = $this->database->getParamsForQuery('DELETE FROM auth_tokens WHERE type = ? AND created <= ?');
		Assert::same(UserAuthTokenType::AdminPasskeyReset->value, $params[0]);
		Assert::type('string', $params[1]); // DateTime formatted by Database mock
	}


	private function getTokens(bool $enabled): PasskeyResetTokens
	{
		return new PasskeyResetTokens(new UserAuthTokens($this->database, 'users'), $this->dateTimeFactory, $enabled, '5 minutes');
	}

}

TestCaseRunner::run(PasskeyResetTokensTest::class);
