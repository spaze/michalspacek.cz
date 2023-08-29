<?php
declare(strict_types = 1);

namespace Form;

use MichalSpacekCz\Form\FormValues;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\InvalidStateException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class FormValuesTest extends TestCase
{

	public function __construct(
		private readonly FormValues $formValues,
	) {
	}


	public function testGetValuesUntrustedValues(): void
	{
		$formData = ['text' => 'foo'];
		$form = new Form();
		$form->addText('text');
		$form->setDefaults($formData);
		$button = $form->addSubmit('submit');
		Assert::same($formData, iterator_to_array($this->formValues->getValues($button)->getIterator()));
		Assert::same($formData, iterator_to_array($this->formValues->getUntrustedValues($button)->getIterator()));
	}


	public function testGetValuesButtonNotAttached(): void
	{
		Assert::exception(function (): void {
			$this->formValues->getValues(new SubmitButton());
		}, InvalidStateException::class, "Component of type 'Nette\Forms\Controls\SubmitButton' is not attached to 'Nette\Forms\Form'.");
	}


	public function testGetUntrustedValuesButtonNotAttached(): void
	{
		Assert::exception(function (): void {
			$this->formValues->getUntrustedValues(new SubmitButton());
		}, InvalidStateException::class, "Component of type 'Nette\Forms\Controls\SubmitButton' is not attached to 'Nette\Forms\Form'.");
	}

}

TestCaseRunner::run(FormValuesTest::class);
