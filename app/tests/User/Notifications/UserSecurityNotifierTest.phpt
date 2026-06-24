<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\User\Notifications;

use LogicException;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NullMailer;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\User\UserAccounts;
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
		$this->seedEmail(42, 'owner@example.com');

		$this->notifier->passkeyAdded(42, '42Password <3');

		$mail = $this->mailer->getMail();
		Assert::same(['owner@example.com' => null], $mail->getHeader('To'));
		Assert::contains('42Password <3', $mail->getBody());
		Assert::contains('https://admin.rizek.test/passkeys', $mail->getBody());
	}


	public function testPasskeyResetEmailsTheUserAndConfirmsOtherAccessWasRevoked(): void
	{
		$this->seedEmail(42, 'owner@example.com');

		$this->notifier->passkeyReset(42, '42Password <3', true);

		$mail = $this->mailer->getMail();
		Assert::same(['owner@example.com' => null], $mail->getHeader('To'));
		Assert::contains('42Password <3', $mail->getBody());
		Assert::contains('messages.notifications.passkeyReset.otherAccessRevoked', $mail->getBody());
	}


	public function testPasskeyResetTellsTheUserWhenOtherAccessCouldNotBeRevoked(): void
	{
		$this->seedEmail(42, 'owner@example.com');

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
		$this->database->setFetchFieldDefaultResult('not-a-valid-ciphertext'); // getEmail()'s decryption throws

		$this->notifier->passkeyAdded(42, '42Password <3'); // best-effort: the failure must not propagate

		Assert::exception(fn() => $this->mailer->getMail(), LogicException::class); // nothing was sent
	}


	private function seedEmail(int $userId, string $address): void
	{
		$this->userAccounts->setEmail($userId, $address);
		$params = $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE id_user = ?');
		$stored = $params[0]['email'];
		assert(is_string($stored));
		$this->database->addFetchFieldResult($stored);
	}

}

TestCaseRunner::run(UserSecurityNotifierTest::class);
