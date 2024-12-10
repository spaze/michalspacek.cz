<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Form\FormComponents;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Arrays;
use Override;
use Stringable;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class SignInHoneypotFormFactoryTest extends TestCase
{

	private UiForm $form;


	public function __construct(
		private readonly FormComponents $formComponents,
		SignInHoneypotFormFactory $signInHoneypotFormFactory,
		ApplicationPresenter $applicationPresenter,
	) {
		$this->form = $signInHoneypotFormFactory->create();
		$applicationPresenter->anchorForm($this->form);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->form->cleanErrors();
	}


	/**
	 * @return list<array{0:string, 1:string, 2:string}>
	 */
	public function getCredentials(): array
	{
		return [
			['foo', 'bar', 'Špatné uživatelské jméno nebo heslo'],
			['foo LIMIT 1', 'bar', 'No, no, no, no, no, no, no, no, no, no, no, no there\'s <a href="https://youtu.be/UKmsUAKWclE?t=8">no <code>limit</code></a>!'],
			['foo', 'honeypot', 'Jo jo, honeypot, přesně tak'],
			['foo OR 1', 'bar', 'Dobrej pokusql!'],
		];
	}


	/** @dataProvider getCredentials */
	public function testCreateOnSuccess(string $username, string $password, string $error): void
	{
		$this->formComponents->setValue($this->form, 'username', $username);
		$this->formComponents->setValue($this->form, 'password', $password);
		Arrays::invoke($this->form->onSuccess, $this->form);
		$formError = $this->form->getErrors()[0];
		assert(is_string($formError) || $formError instanceof Stringable);
		Assert::same($error, (string)$formError);
	}

}

TestCaseRunner::run(SignInHoneypotFormFactoryTest::class);
