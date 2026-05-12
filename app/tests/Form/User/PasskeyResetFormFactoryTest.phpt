<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\User;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\LinkGenerator;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Form\Controls\PasskeyFormControls;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Http\Cookies\Cookies;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\User\WebAuthn\PasskeyAuthenticatorMock;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationAttestationResponseValidatorException;
use MichalSpacekCz\User\WebAuthn\PasskeyReset;
use Nette\Forms\Controls\HiddenField;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Arrays;
use Nette\Utils\Json;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class PasskeyResetFormFactoryTest extends TestCase
{

	private bool $onSuccessCalled = false;


	public function __construct(
		private readonly Database $database,
		private readonly TypedDatabase $typedDatabase,
		private readonly FormFactory $factory,
		private readonly PasskeyFormControls $passkeyFormControls,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly PasskeyAuthenticatorMock $passkeyAuthenticator,
		private readonly Request $httpRequest,
		private readonly LinkGenerator $linkGenerator,
		private readonly Cookies $cookies,
		private readonly Translator $translator,
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


	private function createFormFactory(): PasskeyResetFormFactory
	{
		$manager = new Manager(
			$this->database,
			$this->typedDatabase,
			$this->httpRequest,
			$this->cookies,
			$this->linkGenerator,
			'14 days',
			true,
			'users',
		);
		return new PasskeyResetFormFactory(
			$this->factory,
			$this->passkeyAuthenticator,
			new PasskeyReset($manager, $this->passkeyAuthenticator),
			$this->passkeyFormControls,
			$this->httpRequest,
			$this->translator,
		);
	}


	private function getForm(string $tokenString): UiForm
	{
		$form = $this->createFormFactory()->create(
			function (): void {
				$this->onSuccessCalled = true;
			},
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

TestCaseRunner::run(PasskeyResetFormFactoryTest::class);
