<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\User\WebAuthn\PasskeyAuthenticatorMock;
use MichalSpacekCz\User\AuthTokens\UserAuthToken;
use MichalSpacekCz\User\AuthTokens\UserAuthTokens;
use MichalSpacekCz\User\AuthTokens\UserAuthTokenType;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetInvalidOrExpiredTokenException;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasskeyResetTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly PasskeyAuthenticatorMock $passkeyAuthenticator,
		private readonly DateTimeFactory $dateTimeFactory,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGetUserAuthTokenValid(): void
	{
		$tokenId = 42;
		$tokenValue = 'secret';
		$userId = 1337;
		$username = 'foo';
		$this->database->setFetchDefaultResult([
			'id' => $tokenId,
			'token' => hash('sha512', $tokenValue),
			'userId' => $userId,
			'username' => $username,
		]);
		$passkeyReset = $this->createPasskeyReset();
		$token = $passkeyReset->getUserAuthToken("selector:{$tokenValue}");
		Assert::same($tokenId, $token->getId());
		Assert::same($userId, $token->getUserId());
		Assert::same($username, $token->getUsername());
	}


	public function testGetUserAuthTokenInvalidOrExpired(): void
	{
		Assert::exception(function (): void {
			$this->createPasskeyReset()->getUserAuthToken('selector:invalidtoken');
		}, PasskeyResetInvalidOrExpiredTokenException::class);
	}


	public function testGenerateRegistrationOptions(): void
	{
		$tokenValue = 'secret';
		$this->database->setFetchDefaultResult([
			'id' => 42,
			'token' => hash('sha512', $tokenValue),
			'userId' => 1337,
			'username' => 'foo',
		]);
		$result = $this->createPasskeyReset()->generateRegistrationOptions("selector:{$tokenValue}");
		Assert::same('{}', $result);
	}


	public function testCleanupToken(): void
	{
		$tokenId = 42;
		$this->createPasskeyReset()->cleanupToken(new UserAuthToken($tokenId, 'hash', 1337, 'foo'));
		Assert::same(
			[$tokenId, UserAuthTokenType::AdminPasskeyReset->value],
			$this->database->getParamsForQuery('DELETE FROM auth_tokens WHERE id_auth_token = ? AND type = ?'),
		);
	}


	private function createPasskeyReset(): PasskeyReset
	{
		$tokens = new UserAuthTokens($this->database, 'users');
		$resetTokens = new PasskeyResetTokens($tokens, $this->dateTimeFactory, true);
		return new PasskeyReset($resetTokens, $this->passkeyAuthenticator);
	}

}

TestCaseRunner::run(PasskeyResetTest::class);
