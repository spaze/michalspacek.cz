<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\Notifications;

use LogicException;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NullMailer;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\UserAccounts;
use Nette\Mail\SendException;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class UserSecurityNotifierTest extends TestCase
{

	public function __construct(
		private readonly UserSecurityNotifier $notifier,
		private readonly Database $database,
		private readonly NullMailer $mailer,
		private readonly UserAccounts $userAccounts,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->mailer->reset();
	}


	public function testPasskeyAddedEmailsTheUser(): void
	{
		$this->seedNotificationEmail(42, 'owner@example.com');

		$this->notifier->passkeyAdded(42, '42Password <3');

		$mail = $this->mailer->getMail();
		Assert::same(['owner@example.com' => null], $mail->getHeader('To'));
		Assert::contains('42Password <3', $mail->getBody());
		Assert::contains('https://admin.rizek.test/passkeys', $mail->getBody());
	}


	public function testPasskeyResetEmailsTheUserAndConfirmsOtherAccessWasRevoked(): void
	{
		$this->seedNotificationEmail(42, 'owner@example.com');

		$this->notifier->passkeyReset(42, '42Password <3', true);

		$mail = $this->mailer->getMail();
		Assert::same(['owner@example.com' => null], $mail->getHeader('To'));
		Assert::contains('42Password <3', $mail->getBody());
		Assert::contains('messages.notifications.passkeyReset.otherAccessRevoked', $mail->getBody());
	}


	public function testPasskeyResetTellsTheUserWhenOtherAccessCouldNotBeRevoked(): void
	{
		$this->seedNotificationEmail(42, 'owner@example.com');

		$this->notifier->passkeyReset(42, '42Password <3', false);

		Assert::contains('messages.notifications.passkeyReset.otherAccessRevokeFailed', $this->mailer->getMail()->getBody());
	}


	public function testUserWithoutEmailIsSkipped(): void
	{
		$this->database->setFetchFieldDefaultResult(null);

		$this->notifier->passkeyAdded(42, '42Password <3');

		Assert::exception(fn() => $this->mailer->getMail(), LogicException::class); // nothing was sent
	}


	public function testRecipientLookupFailureIsSwallowed(): void
	{
		$this->database->setFetchFieldDefaultResult('not-a-valid-ciphertext'); // getNotificationEmail()'s decryption throws

		$this->notifier->passkeyAdded(42, '42Password <3'); // best-effort: the failure must not propagate

		Assert::exception(fn() => $this->mailer->getMail(), LogicException::class); // nothing was sent
	}


	public function testEmailChangeAlertsTheOldAddressAndConfirmsTheNew(): void
	{
		$this->notifier->notificationEmailChanged('old@example.com', 'new@example.com');

		$mails = $this->mailer->getAllMails();
		Assert::count(2, $mails);
		Assert::same(['old@example.com' => null], $mails[0]->getHeader('To'));
		Assert::contains('new@example.com', $mails[0]->getBody()); // the old address learns where the email went
		$subject = $mails[0]->getSubject();
		assert(is_string($subject));
		Assert::contains('www.domain.example', $subject);
		Assert::same(['new@example.com' => null], $mails[1]->getHeader('To'));
		Assert::notContains('old@example.com', $mails[1]->getBody()); // the new address must not learn the old one
	}


	public function testFirstEmailSetOnlyConfirmsTheNewAddress(): void
	{
		$this->notifier->notificationEmailChanged(null, 'new@example.com');

		$mails = $this->mailer->getAllMails();
		Assert::count(1, $mails);
		Assert::same(['new@example.com' => null], $mails[0]->getHeader('To'));
	}


	public function testUnchangedEmailNotifiesNothing(): void
	{
		$this->notifier->notificationEmailChanged('same@example.com', 'same@example.com');

		Assert::count(0, $this->mailer->getAllMails());
	}


	public function testEmailChangeStillConfirmsTheNewAddressWhenAlertingTheOldFails(): void
	{
		$this->mailer->willThrowOnce(new SendException('alert delivery failed')); // only the first send, the alert

		$this->notifier->notificationEmailChanged('old@example.com', 'new@example.com'); // best-effort: must not propagate

		$mails = $this->mailer->getAllMails();
		Assert::count(1, $mails); // the failed alert is swallowed and doesn't suppress the confirmation
		Assert::same(['new@example.com' => null], $mails[0]->getHeader('To'));
	}


	private function seedNotificationEmail(int $userId, string $address): void
	{
		$this->userAccounts->setNotificationEmail($userId, $address);
		$params = $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE id_user = ?');
		$stored = $params[0]['notification_email'];
		assert(is_string($stored));
		$this->database->addFetchFieldResult($stored);
	}

}

TestCaseRunner::run(UserSecurityNotifierTest::class);
