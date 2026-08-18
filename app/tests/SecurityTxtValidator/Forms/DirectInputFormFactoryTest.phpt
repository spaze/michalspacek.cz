<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Forms;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Forms\Controls\TextArea;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class DirectInputFormFactoryTest extends TestCase
{

	public function __construct(
		private readonly DirectInputFormFactory $formFactory,
		private readonly ApplicationPresenter $applicationPresenter,
	) {
	}


	private function validateInput(string $value): TextArea
	{
		$form = $this->formFactory->create(function (): void {
		});
		$this->applicationPresenter->anchorForm($form);
		$input = $form->getComponent('input');
		assert($input instanceof TextArea);
		$input->setDefaultValue($value);
		$form->validate([$input]); // just the textarea, so the form's CSRF token doesn't drown out the rule under test
		return $input;
	}


	public function testAcceptsInputUpToTheFetchCap(): void
	{
		$input = $this->validateInput(str_repeat('a', 10000)); // the same 10 kB the fetch path caps at
		Assert::false($input->hasErrors());
	}


	public function testRejectsInputPastTheFetchCap(): void
	{
		$input = $this->validateInput(str_repeat('a', 10001));
		Assert::true($input->hasErrors());
		Assert::contains('too large', implode(' ', $input->getErrors()));
	}

}

TestCaseRunner::run(DirectInputFormFactoryTest::class);
