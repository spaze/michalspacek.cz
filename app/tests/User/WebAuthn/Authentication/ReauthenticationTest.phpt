<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Authentication;

use DateTimeImmutable;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\User\WebAuthn\PasskeyAuthenticatorMock;
use MichalSpacekCz\Test\User\WebAuthn\ReauthenticationRedirectorMock;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyReauthenticationUserMismatchException;
use MichalSpacekCz\User\WebAuthn\Session\PasskeySessionSection;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Override;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class ReauthenticationTest extends TestCase
{

	public function __construct(
		private readonly Reauthentication $reauthentication,
		private readonly PasskeyAuthenticatorMock $passkeyAuthenticator,
		private readonly DateTimeMachineFactory $dateTime,
		private readonly PasskeySessionSection $passkeySessionSection,
		private readonly User $user,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->passkeySessionSection->removeAll();
		$this->dateTime->setDateTime(null);
		$this->user->logout();
		$this->passkeyAuthenticator->wontThrow();
	}


	public function testIsFreshFalseWhenNeverReauthenticated(): void
	{
		Assert::false($this->reauthentication->isFreshAuth());
	}


	public function testRecordReauthMakesItFresh(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2026-06-20 12:00:00'));
		$this->reauthentication->recordFreshAuth();
		Assert::true($this->reauthentication->isFreshAuth());
	}


	public function testGoesStaleAfterTtl(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2026-06-20 12:00:00'));
		$this->reauthentication->recordFreshAuth();
		$this->dateTime->setDateTime(new DateTimeImmutable('2026-06-20 12:06:00')); // reauthTtl is 5 minutes
		Assert::false($this->reauthentication->isFreshAuth());
	}


	public function testVerifyRecordsReauthForSignedInUser(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2026-06-20 12:00:00'));
		$this->user->login(new SimpleIdentity(42));
		$this->passkeyAuthenticator->setAuthenticationResult(new PasskeyAuthenticationResult(42, 'foo', 'cred-id', 'My Passkey'));

		$this->reauthentication->verify('{"id":"test","type":"public-key"}');

		Assert::true($this->reauthentication->isFreshAuth());
	}


	public function testVerifyRejectsADifferentUserAndStaysStale(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2026-06-20 12:00:00'));
		$this->user->login(new SimpleIdentity(42));
		$this->passkeyAuthenticator->setAuthenticationResult(new PasskeyAuthenticationResult(99, 'someone-else', 'cred-id', 'My Passkey'));

		Assert::exception(
			fn() => $this->reauthentication->verify('{}'),
			PasskeyReauthenticationUserMismatchException::class,
		);
		Assert::false($this->reauthentication->isFreshAuth());
	}


	public function testRequireFreshAuthAllowsAccessWhenFresh(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2026-06-20 12:00:00'));
		$this->reauthentication->recordFreshAuth();
		$redirector = new ReauthenticationRedirectorMock();

		$this->reauthentication->requireFreshAuth($redirector);

		Assert::false($redirector->redirected); // confirmed recently, no redirect
	}


	public function testRequireFreshAuthRedirectsWhenStale(): void
	{
		$redirector = new ReauthenticationRedirectorMock();

		Assert::exception(
			fn() => $this->reauthentication->requireFreshAuth($redirector),
			RuntimeException::class,
		);
		Assert::true($redirector->redirected); // not confirmed recently, sent to reauth
	}

}

TestCaseRunner::run(ReauthenticationTest::class);
