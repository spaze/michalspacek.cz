<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NullMailer;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\User\WebAuthn\PasskeyAuthenticatorMock;
use MichalSpacekCz\User\UserAccounts;
use MichalSpacekCz\User\WebAuthn\Authentication\PasskeyAuthenticationResult;
use MichalSpacekCz\User\WebAuthn\Session\PasskeySessionSection;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\HiddenField;
use Nette\Forms\Controls\TextInput;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Utils\Arrays;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/**
 * The email change is confirmed in place by a passkey: the form's validation verifies the passkey
 * assertion, and only a verified submission reaches the save. A confirmation that fails (here, a
 * passkey belonging to a different user) adds a form error and the email is not written.
 *
 * @testCase
 */
final class AccountEmailFormFactoryTest extends TestCase
{

	private bool $onSuccessCalled = false;


	public function __construct(
		private readonly AccountEmailFormFactory $formFactory,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly PasskeyAuthenticatorMock $passkeyAuthenticator,
		private readonly PasskeySessionSection $session,
		private readonly User $user,
		private readonly Database $database,
		private readonly NullMailer $mailer,
		private readonly UserAccounts $userAccounts,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->session->removeAll();
		$this->user->logout();
		$this->database->reset();
		$this->mailer->reset();
		$this->onSuccessCalled = false;
	}


	public function testVerifiedPasskeySavesEncryptedEmail(): void
	{
		$this->user->login(new SimpleIdentity(42));
		$this->passkeyAuthenticator->setAuthenticationResult(new PasskeyAuthenticationResult(42, 'foo', 'cred-id'));
		$this->database->setFetchFieldDefaultResult(null); // no current email to prefill
		$form = $this->createForm('me@example.com', '{"id":"test","type":"public-key"}');

		Arrays::invoke($form->onValidate, $form);
		Assert::false($form->hasErrors());
		Arrays::invoke($form->onSuccess, $form);

		Assert::true($this->onSuccessCalled);
		$params = $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE id_user = ?');
		$stored = $params[0]['email'];
		assert(is_string($stored));
		Assert::notSame('me@example.com', $stored); // stored encrypted, never plaintext
		Assert::same(['me@example.com' => null], $this->mailer->getMail()->getHeader('To')); // first-set confirms the new address
	}


	public function testCapturesTheOldEmailBeforeOverwriting(): void
	{
		$this->user->login(new SimpleIdentity(42));
		$this->passkeyAuthenticator->setAuthenticationResult(new PasskeyAuthenticationResult(42, 'foo', 'cred-id'));
		$this->seedCurrentEmail('old@example.com');
		$form = $this->createForm('new@example.com', '{"id":"test","type":"public-key"}');

		Arrays::invoke($form->onValidate, $form);
		Arrays::invoke($form->onSuccess, $form);

		// an alert to the OLD address only happens if onSuccess read the old email before setEmail overwrote it
		$mails = $this->mailer->getAllMails();
		Assert::count(2, $mails);
		Assert::same(['old@example.com' => null], $mails[0]->getHeader('To'));
		Assert::same(['new@example.com' => null], $mails[1]->getHeader('To'));
	}


	public function testWrongUserPasskeyAddsErrorAndDoesNotSave(): void
	{
		$this->user->login(new SimpleIdentity(42));
		$this->passkeyAuthenticator->setAuthenticationResult(new PasskeyAuthenticationResult(99, 'foo', 'cred-id')); // someone else's passkey
		$this->database->setFetchFieldDefaultResult(null);
		$form = $this->createForm('new@example.com', '{"id":"test","type":"public-key"}');

		Arrays::invoke($form->onValidate, $form);

		Assert::true($form->hasErrors());
		Assert::false($this->onSuccessCalled);
		Assert::same([], $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE id_user = ?')); // not saved
	}


	private function seedCurrentEmail(string $address): void
	{
		$this->userAccounts->setEmail(42, $address);
		$params = $this->database->getParamsArrayForQuery('UPDATE ?name SET ? WHERE id_user = ?');
		$stored = $params[0]['email'];
		assert(is_string($stored));
		$this->database->setFetchFieldDefaultResult($stored); // every getEmail() returns the seeded address until overwritten
	}


	private function createForm(string $email, string $credential): Form
	{
		$form = $this->formFactory->create(function (): void {
			$this->onSuccessCalled = true;
		});
		$this->applicationPresenter->anchorForm($form);
		$emailField = $form->getComponent('email');
		assert($emailField instanceof TextInput);
		$emailField->setDefaultValue($email);
		$credentialField = $form->getComponent('credential');
		assert($credentialField instanceof HiddenField);
		$credentialField->setDefaultValue($credential);
		return $form;
	}

}

TestCaseRunner::run(AccountEmailFormFactoryTest::class);
