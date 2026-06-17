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
use MichalSpacekCz\User\WebAuthn\PasskeyStorage;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationAttestationResponseValidatorException;
use MichalSpacekCz\User\WebAuthn\Registration\PasskeyAdd;
use MichalSpacekCz\User\WebAuthn\Registration\PasskeyAddTokens;
use MichalSpacekCz\User\WebAuthn\Registration\PasskeyRegistration;
use MichalSpacekCz\User\WebAuthn\Registration\PasskeyReset;
use MichalSpacekCz\User\WebAuthn\Registration\PasskeyResetRevoker;
use MichalSpacekCz\User\WebAuthn\Registration\PasskeyResetTokens;
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

	private bool $onSuccessRevokeFailed = false;


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
		private readonly PasskeyStorage $passkeyStorage,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->onSuccessCalled = false;
		$this->onSuccessRevokeFailed = false;
		$this->passkeyAuthenticator->wontThrow();
		$this->user->logout();
	}


	public function testOnSuccess(): void
	{
		$this->setUpTokenInDb(42, 1337, 'foo');
		$form = $this->getForm($this->createPasskeyAdd(), 'selector:secret');
		Arrays::invoke($form->onSuccess, $form);
		Assert::true($this->onSuccessCalled);
		Assert::false($this->onSuccessRevokeFailed);
		Assert::same([], $form->getErrors());
	}


	public function testOnError(): void
	{
		$this->setUpTokenInDb(42, 1337, 'foo');
		$this->passkeyAuthenticator->willThrow(new PasskeyRegistrationAttestationResponseValidatorException());
		$form = $this->getForm($this->createPasskeyAdd(), 'selector:secret');
		Arrays::invoke($form->onSuccess, $form);
		Assert::false($this->onSuccessCalled);
		Assert::count(1, $form->getErrors());
	}


	public function testInvalidToken(): void
	{
		$form = $this->getForm($this->createPasskeyAdd(), 'selector:invalidtoken');
		Arrays::invoke($form->onSuccess, $form);
		Assert::false($this->onSuccessCalled);
		Assert::count(1, $form->getErrors());
	}


	public function testSignedInAsDifferentUserIsRefused(): void
	{
		$this->setUpTokenInDb(42, 1337, 'foo');
		$this->user->login(new SimpleIdentity(9999));
		$form = $this->getForm($this->createPasskeyAdd(), 'selector:secret');
		Arrays::invoke($form->onSuccess, $form);
		Assert::false($this->onSuccessCalled);
		Assert::count(1, $form->getErrors());
	}


	public function testResetRevokeFailureIsReportedAsSuccessWithTheFlag(): void
	{
		$this->setUpTokenInDb(42, 1337, 'foo');
		// No kept-credential result set up, so the reset's revoke fails after the passkey is registered
		$form = $this->getForm($this->createPasskeyReset(), 'selector:secret');
		Arrays::invoke($form->onSuccess, $form);
		Assert::true($this->onSuccessCalled); // registration succeeded, so we still proceed
		Assert::true($this->onSuccessRevokeFailed); // ...but the presenter is told to warn
		Assert::same([], $form->getErrors()); // a revoke failure is not a registration error
	}


	private function setUpTokenInDb(int $tokenId, int $userId, string $username): void
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
		return new PasskeyAdd($addTokens, $this->passkeyAuthenticator, $this->user);
	}


	private function createPasskeyReset(): PasskeyReset
	{
		$resetTokens = new PasskeyResetTokens(new UserAuthTokens($this->database, 'users'), $this->dateTimeFactory, true, '5 minutes');
		return new PasskeyReset($resetTokens, $this->passkeyAuthenticator, $this->user, new PasskeyResetRevoker($this->passkeyStorage, []));
	}


	private function getForm(PasskeyRegistration $registration, string $tokenString): Form
	{
		$factory = new PasskeyRegistrationFormFactory(
			$this->factory,
			$registration,
			$this->passkeyFormControls,
			$this->httpRequest,
			$this->translator,
		);
		$form = $factory->create(
			function (bool $otherAccessRevokeFailed): void {
				$this->onSuccessCalled = true;
				$this->onSuccessRevokeFailed = $otherAccessRevokeFailed;
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
