<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Security\NullUserStorage;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\User\WebAuthn\PasskeyAuthenticatorMock;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationAttestationResponseValidatorException;
use Nette\Forms\Controls\HiddenField;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Nette\Security\User;
use Nette\Utils\Arrays;
use Nette\Utils\Json;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasskeyRegisterFormFactoryTest extends TestCase
{

	private bool $onSuccessCalled = false;


	public function __construct(
		private readonly Database $database,
		private readonly Manager $authenticator,
		private readonly NullUserStorage $userStorage,
		private readonly PasskeyRegisterFormFactory $formFactory,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly PasskeyAuthenticatorMock $passkeyAuthenticator,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->onSuccessCalled = false;
		$this->passkeyAuthenticator->wontThrow();
	}


	public function testOnSuccess(): void
	{
		$form = $this->getForm();
		Arrays::invoke($form->onSuccess, $form);
		Assert::true($this->onSuccessCalled);
		Assert::same([], $form->getErrors());
	}


	public function testOnError(): void
	{
		$this->passkeyAuthenticator->willThrow(new PasskeyRegistrationAttestationResponseValidatorException());
		$form = $this->getForm();
		Arrays::invoke($form->onSuccess, $form);
		Assert::false($this->onSuccessCalled);
		Assert::count(1, $form->getErrors());
	}


	private function getForm(): Form
	{
		$this->database->setFetchFieldDefaultResult('0xPasskeysUserHandle');
		$identity = $this->authenticator->getIdentity(1337, 'foo');
		$this->userStorage->saveAuthentication($identity);
		$user = new User($this->userStorage);
		$form = $this->formFactory->create(
			function (): void {
				$this->onSuccessCalled = true;
			},
			$user,
			'https://url.example/foo/bar/error',
			'https://url.example/foo/bar/canceled',
			'https://url.example/foo/bar/not-supported',
		);
		$this->applicationPresenter->anchorForm($form);

		$nameField = $form->getComponent('name');
		assert($nameField instanceof TextInput);
		$nameField->setDefaultValue('My Passkey');

		$credentialField = $form->getComponent('credential');
		assert($credentialField instanceof HiddenField);
		$credentialField->setDefaultValue(Json::encode(['id' => 'test', 'type' => 'public-key']));
		return $form;
	}

}

TestCaseRunner::run(PasskeyRegisterFormFactoryTest::class);
