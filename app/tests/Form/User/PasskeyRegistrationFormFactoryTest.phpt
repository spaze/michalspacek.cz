<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use Contributte\Translation\Translator;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\Form\Controls\PasskeyFormControls;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\User\WebAuthn\PasskeyAuthenticatorMock;
use MichalSpacekCz\User\AuthTokens\UserAuthTokens;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationAttestationResponseValidatorException;
use MichalSpacekCz\User\WebAuthn\PasskeyRegistration;
use MichalSpacekCz\User\WebAuthn\PasskeyResetTokens;
use Nette\Forms\Controls\HiddenField;
use Nette\Forms\Controls\TextInput;
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
final class PasskeyRegistrationFormFactoryTest extends TestCase
{

	private bool $onSuccessCalled = false;


	public function __construct(
		private readonly Database $database,
		private readonly FormFactory $factory,
		private readonly PasskeyFormControls $passkeyFormControls,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly PasskeyAuthenticatorMock $passkeyAuthenticator,
		private readonly Request $httpRequest,
		private readonly Translator $translator,
		private readonly DateTimeFactory $dateTimeFactory,
		private readonly User $user,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->onSuccessCalled = false;
		$this->passkeyAuthenticator->wontThrow();
		$this->user->logout();
	}


	public function testOnSuccess(): void
	{
		$tokenString = $this->setUpTokenInDb(42, 1337, 'foo');
		$form = $this->getForm($tokenString);
		Arrays::invoke($form->onSuccess, $form);
		Assert::true($this->onSuccessCalled);
		Assert::same([], $form->getErrors());
	}


	public function testOnError(): void
	{
		$tokenString = $this->setUpTokenInDb(42, 1337, 'foo');
		$this->passkeyAuthenticator->willThrow(new PasskeyRegistrationAttestationResponseValidatorException());
		$form = $this->getForm($tokenString);
		Arrays::invoke($form->onSuccess, $form);
		Assert::false($this->onSuccessCalled);
		Assert::count(1, $form->getErrors());
	}


	public function testInvalidToken(): void
	{
		$form = $this->getForm('selector:invalidtoken');
		Arrays::invoke($form->onSuccess, $form);
		Assert::false($this->onSuccessCalled);
		Assert::count(1, $form->getErrors());
	}


	public function testSignedInAsDifferentUserIsRefused(): void
	{
		$tokenString = $this->setUpTokenInDb(42, 1337, 'foo');
		$this->user->login(new SimpleIdentity(9999));
		$form = $this->getForm($tokenString);
		Arrays::invoke($form->onSuccess, $form);
		Assert::false($this->onSuccessCalled);
		Assert::count(1, $form->getErrors());
	}


	private function setUpTokenInDb(int $tokenId, int $userId, string $username): string
	{
		$tokenValue = 'secret';
		$this->database->setFetchDefaultResult([
			'id' => $tokenId,
			'token' => hash('sha512', $tokenValue),
			'userId' => $userId,
			'username' => $username,
		]);
		return "selector:{$tokenValue}";
	}


	private function createFormFactory(): PasskeyRegistrationFormFactory
	{
		$resetTokens = new PasskeyResetTokens(new UserAuthTokens($this->database, 'users'), $this->dateTimeFactory, true, '5 minutes');
		return new PasskeyRegistrationFormFactory(
			$this->factory,
			$this->passkeyAuthenticator,
			new PasskeyRegistration($resetTokens, $this->passkeyAuthenticator, $this->user),
			$this->passkeyFormControls,
			$this->httpRequest,
			$this->translator,
		);
	}


	private function getForm(string $tokenString): Form
	{
		$form = $this->createFormFactory()->create(
			function (): void {
				$this->onSuccessCalled = true;
			},
			'https://url.example/foo/bar/options',
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

		$tokenField = $form->getComponent('token');
		assert($tokenField instanceof HiddenField);
		$tokenField->setDefaultValue($tokenString);

		return $form;
	}

}

TestCaseRunner::run(PasskeyRegistrationFormFactoryTest::class);
