<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\User\WebAuthn\PasskeyAuthenticatorMock;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyAuthenticationUnknownCredentialException;
use MichalSpacekCz\User\WebAuthn\Authentication\PasskeyAuthenticationResult;
use MichalSpacekCz\User\WebAuthn\Authentication\Reauthentication;
use MichalSpacekCz\User\WebAuthn\Session\PasskeySessionSection;
use Nette\Forms\Controls\HiddenField;
use Nette\Forms\Form;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Utils\Arrays;
use Nette\Utils\Json;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasskeyReauthenticateFormFactoryTest extends TestCase
{

	private bool $onSuccessCalled = false;


	public function __construct(
		private readonly PasskeyReauthenticateFormFactory $formFactory,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly PasskeyAuthenticatorMock $passkeyAuthenticator,
		private readonly User $user,
		private readonly Reauthentication $reauthentication,
		private readonly PasskeySessionSection $passkeySessionSection,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->passkeySessionSection->removeAll();
		$this->user->logout();
		$this->onSuccessCalled = false;
		$this->passkeyAuthenticator->wontThrow();
	}


	public function testValidPasskeyRecordsReauthAndReachesSuccess(): void
	{
		$this->user->login(new SimpleIdentity(42));
		$this->passkeyAuthenticator->setAuthenticationResult(new PasskeyAuthenticationResult(42, 'foo', 'cred-id'));

		$form = $this->createForm();
		Arrays::invoke($form->onValidate, $form);

		Assert::count(0, $form->getErrors());
		Assert::true($this->reauthentication->isFreshAuth());

		Arrays::invoke($form->onSuccess, $form);
		Assert::true($this->onSuccessCalled);
	}


	public function testWrongUserAddsErrorAndDoesNotReauth(): void
	{
		$this->user->login(new SimpleIdentity(42));
		$this->passkeyAuthenticator->setAuthenticationResult(new PasskeyAuthenticationResult(99, 'someone-else', 'cred-id'));

		$form = $this->createForm();
		Arrays::invoke($form->onValidate, $form);

		Assert::count(1, $form->getErrors());
		Assert::false($this->reauthentication->isFreshAuth());
		Assert::false($this->onSuccessCalled);
	}


	public function testAssertionErrorAddsError(): void
	{
		$this->user->login(new SimpleIdentity(42));
		$this->passkeyAuthenticator->willThrow(new PasskeyAuthenticationUnknownCredentialException('foo'));

		$form = $this->createForm();
		Arrays::invoke($form->onValidate, $form);

		Assert::count(1, $form->getErrors());
		Assert::false($this->reauthentication->isFreshAuth());
		Assert::false($this->onSuccessCalled);
	}


	private function createForm(): Form
	{
		$form = $this->formFactory->create(
			function (): void {
				$this->onSuccessCalled = true;
			},
			'https://error-url.example/',
			'https://canceled-url.example/',
		);
		$this->applicationPresenter->anchorForm($form);
		$field = $form->getComponent('credential');
		assert($field instanceof HiddenField);
		$field->setDefaultValue(Json::encode(['id' => 'test', 'type' => 'public-key']));
		return $form;
	}

}

TestCaseRunner::run(PasskeyReauthenticateFormFactoryTest::class);
