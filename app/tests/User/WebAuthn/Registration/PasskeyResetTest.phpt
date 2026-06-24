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
use MichalSpacekCz\User\Notifications\UserSecurityNotifier;
use MichalSpacekCz\User\UserAccounts;
use MichalSpacekCz\User\WebAuthn\PasskeyStorage;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyResetRevokeFailedException;
use Nette\Security\User;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class PasskeyResetTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly PasskeyAuthenticatorMock $passkeyAuthenticator,
		private readonly DateTimeFactory $dateTimeFactory,
		private readonly User $user,
		private readonly PasskeyStorage $passkeyStorage,
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


	public function testRegisterSavesPasskeyThenRevokesOtherAccessKeepingTheNewOne(): void
	{
		$userId = 1337;
		$this->setUpToken(42, $userId, 'foo');
		$this->database->setFetchFieldDefaultResult(1); // the just-registered credential exists, so the revoke runs

		$result = $this->createPasskeyReset()->register('{"id":"test","type":"public-key"}', 'My Passkey', 'selector:secret');

		Assert::same($userId, $result->userId);
		Assert::same('mockPasskeyCredentialId', $result->keepCredentialId);
		Assert::null($result->revokeFailure);
		Assert::same(
			['passkeys', $userId, 'mockPasskeyCredentialId'], // every other passkey deleted, the just-registered one kept
			$this->database->getParamsForQuery('DELETE FROM ?name WHERE key_user = ? AND credential_id != ?'),
		);
	}


	public function testRegisterReportsRevokeFailureWithoutThrowing(): void
	{
		$this->setUpToken(42, 1337, 'foo');
		// No kept-credential result set up, so the revoke's passkey delete throws and aggregates into a failure

		$result = $this->createPasskeyReset()->register('{}', 'My Passkey', 'selector:secret');

		Assert::same('mockPasskeyCredentialId', $result->keepCredentialId); // the passkey was still registered
		Assert::type(PasskeyResetRevokeFailedException::class, $result->revokeFailure);
	}


	public function testGenerateRegistrationOptionsAllowsReEnrollment(): void
	{
		$this->setUpToken(42, 1337, 'foo');
		$this->createPasskeyReset()->generateRegistrationOptions('selector:secret');
		// Reset is recovery: it must NOT exclude existing credentials, so you can re-enrol on an authenticator that already holds one
		Assert::false($this->passkeyAuthenticator->lastExcludeExistingCredentials);
	}


	public function testRegisterNotifiesTheUserByEmail(): void
	{
		$userId = 1337;
		$this->setUpToken(42, $userId, 'foo');
		$this->database->addFetchFieldResult(1); // the revoke's kept-credential check runs before the notify, so it consumes this first
		$this->seedEmail($userId, 'owner@example.com'); // then the notify's getEmail() reads the queued ciphertext

		$this->createPasskeyReset()->register('{"id":"test","type":"public-key"}', 'My Passkey', 'selector:secret');

		Assert::same(['owner@example.com' => null], $this->mailer->getMail()->getHeader('To'));
	}


	private function seedEmail(int $userId, string $address): void
	{
		$this->userAccounts->setEmail($userId, $address);
		$params = $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE id_user = ?');
		$stored = $params[0]['email'];
		assert(is_string($stored));
		$this->database->addFetchFieldResult($stored);
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


	private function createPasskeyReset(): PasskeyReset
	{
		$resetTokens = new PasskeyResetTokens(new UserAuthTokens($this->database, 'users'), $this->dateTimeFactory, true, '5 minutes');
		$revoker = new PasskeyResetRevoker($this->passkeyStorage, []);
		return new PasskeyReset($resetTokens, $this->passkeyAuthenticator, $this->user, $this->notifier, $revoker);
	}

}

TestCaseRunner::run(PasskeyResetTest::class);
