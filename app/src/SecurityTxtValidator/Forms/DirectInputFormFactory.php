<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Forms;

use MichalSpacekCz\Form\FormFactory;
use Nette\Forms\Control;
use Nette\Forms\Form;

final readonly class DirectInputFormFactory
{

	public function __construct(
		private FormFactory $factory,
	) {
	}


	/**
	 * @param callable(string): void $onSuccess
	 */
	public function create(callable $onSuccess): Form
	{
		$form = $this->factory->create();
		$form->getElementPrototype()->id = 'form-direct-input';
		$form->addTextArea('input', 'Input')
			->setHtmlId('direct-input')
			->addRule(fn(Control $input): bool => is_string($input->getValue()) && trim($input->getValue()) !== '', 'Enter the contents of the file, like at least one letter')
			->setRequired('Enter the contents of the security.txt file');
		$form->addSubmit('validate', 'Validate');
		$form->onSuccess[] = function (Form $form) use ($onSuccess): void {
			$values = $form->getValues();
			assert(is_string($values->input));
			$onSuccess($values->input);
		};
		return $form;
	}

}
