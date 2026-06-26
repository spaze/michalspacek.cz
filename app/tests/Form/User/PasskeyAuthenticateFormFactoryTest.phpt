<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Security\NullUserStorage;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\User\WebAuthn\PasskeyAuthenticatorMock;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyAuthenticationUnknownCredentialException;
use MichalSpacekCz\User\WebAuthn\Authentication\PasskeyAuthenticationResult;
use MichalSpacekCz\User\WebAuthn\Authentication\Reauthentication;
use Nette\Forms\Controls\HiddenField;
use Nette\Security\User;
use Nette\Utils\Arrays;
use Nette\Utils\Json;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasskeyAuthenticateFormFactoryTest extends TestCase
{

	private bool $onSuccessCalled = false;


	public function __construct(
		private readonly PasskeyAuthenticateFormFactory $formFactory,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly PasskeyAuthenticatorMock $passkeyAuthenticator,
		private readonly User $user,
		private readonly NullUserStorage $userStorage,
		private readonly Reauthentication $reauthentication,
		private readonly Database $database,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->user->logout();
		$this->onSuccessCalled = false;
		$this->passkeyAuthenticator->wontThrow();
		$this->database->reset();
	}


	public function testOnSuccess(): void
	{
		$userId = 1337;
		$username = 'foo';
		$this->passkeyAuthenticator->setAuthenticationResult(new PasskeyAuthenticationResult($userId, $username, 'test-credential-id'));

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

		Arrays::invoke($form->onSuccess, $form);
		Assert::true($this->onSuccessCalled);
		Assert::true($this->user->isLoggedIn());
		Assert::same($userId, $this->user->getId());
		Assert::same('30 minutes', $this->userStorage->expire);
		Assert::true($this->userStorage->clearIdentity);
		Assert::true($this->reauthentication->isFreshAuth()); // signing in with a passkey counts as confirming identity
		Assert::same('signin.success', $this->database->getParamsArrayForQuery('INSERT INTO security_events')[0]['action']);
	}


	public function testOnError(): void
	{
		$this->passkeyAuthenticator->willThrow(new PasskeyAuthenticationUnknownCredentialException('foo'));

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
		$field->setDefaultValue(Json::encode(['id' => 'bad', 'type' => 'public-key']));

		Arrays::invoke($form->onSuccess, $form);
		Assert::false($this->onSuccessCalled);
		Assert::false($this->user->isLoggedIn());
		Assert::count(1, $form->getErrors());
	}

}

TestCaseRunner::run(PasskeyAuthenticateFormFactoryTest::class);
