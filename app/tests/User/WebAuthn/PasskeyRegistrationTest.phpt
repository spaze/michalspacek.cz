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
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationInvalidOrExpiredTokenException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationUserMismatchException;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasskeyRegistrationTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly PasskeyAuthenticatorMock $passkeyAuthenticator,
		private readonly DateTimeFactory $dateTimeFactory,
		private readonly User $user,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->user->logout();
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
		$passkeyRegistration = $this->createPasskeyRegistration();
		$token = $passkeyRegistration->getUserAuthToken("selector:{$tokenValue}");
		Assert::same($tokenId, $token->getId());
		Assert::same($userId, $token->getUserId());
		Assert::same($username, $token->getUsername());
	}


	public function testGetUserAuthTokenInvalidOrExpired(): void
	{
		Assert::exception(function (): void {
			$this->createPasskeyRegistration()->getUserAuthToken('selector:invalidtoken');
		}, PasskeyRegistrationInvalidOrExpiredTokenException::class);
	}


	public function testGetUserAuthTokenSignedInUserMatches(): void
	{
		$userId = 1337;
		$tokenValue = 'secret';
		$this->database->setFetchDefaultResult([
			'id' => 42,
			'token' => hash('sha512', $tokenValue),
			'userId' => $userId,
			'username' => 'foo',
		]);
		$this->user->login(new SimpleIdentity($userId));
		$token = $this->createPasskeyRegistration()->getUserAuthToken("selector:{$tokenValue}");
		Assert::same($userId, $token->getUserId());
	}


	public function testGetUserAuthTokenSignedInAsDifferentUserThrows(): void
	{
		$tokenValue = 'secret';
		$this->database->setFetchDefaultResult([
			'id' => 42,
			'token' => hash('sha512', $tokenValue),
			'userId' => 1337,
			'username' => 'foo',
		]);
		$this->user->login(new SimpleIdentity(9999));
		Assert::exception(function () use ($tokenValue): void {
			$this->createPasskeyRegistration()->getUserAuthToken("selector:{$tokenValue}");
		}, PasskeyRegistrationUserMismatchException::class);
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
		$result = $this->createPasskeyRegistration()->generateRegistrationOptions("selector:{$tokenValue}");
		Assert::same('{}', $result);
	}


	public function testCleanupToken(): void
	{
		$tokenId = 42;
		$this->createPasskeyRegistration()->cleanupToken(new UserAuthToken($tokenId, 'hash', 1337, 'foo'));
		Assert::same(
			[$tokenId, UserAuthTokenType::AdminPasskeyReset->value],
			$this->database->getParamsForQuery('DELETE FROM auth_tokens WHERE id_auth_token = ? AND type = ?'),
		);
	}


	private function createPasskeyRegistration(): PasskeyRegistration
	{
		$tokens = new UserAuthTokens($this->database, 'users');
		$resetTokens = new PasskeyResetTokens($tokens, $this->dateTimeFactory, true, '5 minutes');
		return new PasskeyRegistration($resetTokens, $this->passkeyAuthenticator, $this->user);
	}

}

TestCaseRunner::run(PasskeyRegistrationTest::class);
