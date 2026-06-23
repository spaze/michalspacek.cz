<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration;

use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NullMailer;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\User\WebAuthn\PasskeyAuthenticatorMock;
use MichalSpacekCz\User\AuthTokens\UserAuthTokens;
use MichalSpacekCz\User\AuthTokens\UserAuthTokenType;
use MichalSpacekCz\User\Notifications\UserSecurityNotifier;
use MichalSpacekCz\User\UserAccounts;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationInvalidOrExpiredTokenException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationUserMismatchException;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class PasskeyAddTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly PasskeyAuthenticatorMock $passkeyAuthenticator,
		private readonly DateTimeFactory $dateTimeFactory,
		private readonly User $user,
		private readonly UserSecurityNotifier $notifier,
		private readonly UserAccounts $userAccounts,
		private readonly NullMailer $mailer,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->mailer->reset();
		$this->user->logout();
	}


	public function testRegisterSavesPasskeyConsumesTokenAndDoesNotRevoke(): void
	{
		$tokenId = 42;
		$userId = 1337;
		$this->setUpToken($tokenId, $userId, 'foo');

		$result = $this->createPasskeyAdd()->register('{"id":"test","type":"public-key"}', 'My Passkey', 'selector:secret');

		Assert::same('foo', $result->username);
		Assert::same($userId, $result->userId);
		Assert::same('mockPasskeyCredentialId', $result->keepCredentialId);
		Assert::null($result->revokeFailure); // add keeps the user's other passkeys, so it never revokes
		Assert::same(
			[$tokenId, UserAuthTokenType::AdminPasskeyAdd->value],
			$this->database->getParamsForQuery('DELETE FROM auth_tokens WHERE id_auth_token = ? AND type = ?'),
		);
	}


	public function testRegisterInvalidOrExpiredToken(): void
	{
		Assert::exception(function (): void {
			$this->createPasskeyAdd()->register('{}', 'My Passkey', 'selector:invalidtoken');
		}, PasskeyRegistrationInvalidOrExpiredTokenException::class);
	}


	public function testRegisterSignedInAsDifferentUserThrows(): void
	{
		$this->setUpToken(42, 1337, 'foo');
		$this->user->login(new SimpleIdentity(9999));
		Assert::exception(function (): void {
			$this->createPasskeyAdd()->register('{}', 'My Passkey', 'selector:secret');
		}, PasskeyRegistrationUserMismatchException::class);
	}


	public function testGenerateRegistrationOptionsExcludesExistingCredentials(): void
	{
		$this->setUpToken(42, 1337, 'foo');
		Assert::same('{}', $this->createPasskeyAdd()->generateRegistrationOptions('selector:secret'));
		// Add must exclude the user's existing passkeys, so an authenticator that already holds one won't register a duplicate
		Assert::true($this->passkeyAuthenticator->lastExcludeExistingCredentials);
	}


	public function testRegisterNotifiesTheUserByEmail(): void
	{
		$userId = 1337;
		$this->setUpToken(42, $userId, 'foo');
		$this->seedEmail($userId, 'owner@example.com');

		$this->createPasskeyAdd()->register('{"id":"test","type":"public-key"}', 'My Passkey', 'selector:secret');

		Assert::same(['owner@example.com' => null], $this->mailer->getMail()->getHeader('To'));
	}


	private function seedEmail(int $userId, string $address): void
	{
		$this->userAccounts->setEmail($userId, $address);
		$params = $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE id_user = ?');
		$stored = $params[0]['email'];
		assert(is_string($stored));
		$this->database->setFetchFieldDefaultResult($stored);
	}


	private function setUpToken(int $tokenId, int $userId, string $username): void
	{
		$this->database->setFetchDefaultResult([
			'id' => $tokenId,
			'token' => hash('sha512', 'secret'),
			'userId' => $userId,
			'username' => $username,
		]);
	}


	private function createPasskeyAdd(): PasskeyAdd
	{
		$addTokens = new PasskeyAddTokens(new UserAuthTokens($this->database, 'users'), $this->dateTimeFactory, true, '5 minutes');
		return new PasskeyAdd($addTokens, $this->passkeyAuthenticator, $this->user, $this->notifier);
	}

}

TestCaseRunner::run(PasskeyAddTest::class);
