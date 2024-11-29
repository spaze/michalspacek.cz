<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Arrays;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class SignInHoneypotFormFactoryTest extends TestCase
{

	private UiForm $form;


	public function __construct(
		SignInHoneypotFormFactory $signInHoneypotFormFactory,
		ApplicationPresenter $applicationPresenter,
	) {
		$this->form = $signInHoneypotFormFactory->create();
		$presenter = $applicationPresenter->createUiPresenter('Admin:Honeypot', 'foo', 'signIn');
		/** @noinspection PhpInternalEntityUsedInspection */
		$this->form->setParent($presenter);
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
		$this->setValue('username', $username);
		$this->setValue('password', $password);
		Arrays::invoke($this->form->onSuccess, $this->form);
		Assert::same($error, (string)$this->form->getErrors()[0]);
	}


	private function setValue(string $component, string $value): void
	{
		$field = $this->form->getComponent($component);
		if (!$field instanceof TextInput) {
			Assert::fail('Field is of a wrong type ' . $field::class);
		} else {
			$field->setDefaultValue($value);
		}
	}

}

TestCaseRunner::run(SignInHoneypotFormFactoryTest::class);
