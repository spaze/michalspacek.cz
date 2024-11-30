<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Form\FormComponents;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\DI\Container;
use Nette\Security\Passwords;
use Nette\Security\User;
use Nette\Utils\Arrays;
use Override;
use Spaze\Encryption\SymmetricKeyEncryption;
use Stringable;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class SignInFormFactoryTest extends TestCase
{

	private readonly UiForm $form;
	private readonly SymmetricKeyEncryption $passwordEncryption;


	public function __construct(
		private readonly Database $database,
		private readonly Passwords $passwords,
		private readonly User $user,
		private readonly NullLogger $logger,
		private readonly FormComponents $formComponents,
		Request $httpRequest,
		SignInFormFactory $formFactory,
		ApplicationPresenter $applicationPresenter,
		Container $container,
	) {
		$httpRequest->setRemoteAddress('127.31.33.7');
		$this->form = $formFactory->create(function () {
		});
		$presenter = $applicationPresenter->createUiPresenter('Admin:Sign', 'foo', 'in');
		/** @noinspection PhpInternalEntityUsedInspection */
		$this->form->setParent($presenter);

		$service = $container->getService('passwordEncryption');
		assert($service instanceof SymmetricKeyEncryption);
		$this->passwordEncryption = $service;
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->logger->reset();
		$this->form->cleanErrors();
	}


	/**
	 * @return list<array{0:string, 1:string, 2:string|null}>
	 */
	public function getCredentials(): array
	{
		return [
			['root', 'hunter2', null],
			['root', 'hunter3', 'Špatné uživatelské jméno nebo heslo'],
		];
	}


	/**
	 * @dataProvider getCredentials
	 */
	public function testCreateOnSuccess(string $username, string $password, ?string $message): void
	{
		$this->database->setFetchResult([
			'userId' => 123,
			'username' => 'root',
			'password' => $this->passwordEncryption->encrypt($this->passwords->hash('hunter2')),
		]);
		$this->formComponents->setValue($this->form, 'username', $username);
		$this->formComponents->setValue($this->form, 'password', $password);
		Arrays::invoke($this->form->onSuccess, $this->form);
		if ($message === null) {
			Assert::true($this->user->isLoggedIn());
			Assert::count(0, $this->form->getErrors());
			Assert::same(['Successful sign-in attempt (root, 127.31.33.7)'], $this->logger->getLogged());
		} else {
			Assert::false($this->user->isLoggedIn());
			Assert::count(1, $this->form->getErrors());
			$formError = $this->form->getErrors()[0];
			assert(is_string($formError) || $formError instanceof Stringable);
			Assert::same($message, (string)$formError);
			Assert::same(['Failed sign-in attempt: The password is incorrect. (root, 127.31.33.7)'], $this->logger->getLogged());
		}
	}

}

TestCaseRunner::run(SignInFormFactoryTest::class);
