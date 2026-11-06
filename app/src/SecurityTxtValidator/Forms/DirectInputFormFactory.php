<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Forms;

use Nette\Application\UI\Form;
use Nette\Forms\Control;

final class DirectInputFormFactory
{

	private const int MAX_BYTES = 10_000; // The same as the fetcher's default `maxResponseLength`


	/**
	 * @param callable(string): void $onSuccess
	 */
	public function create(callable $onSuccess): Form
	{
		$form = new Form();
		$form->getElementPrototype()->id = 'form-direct-input';
		$form->addTextArea('input', 'Enter the contents of your security.txt file')
			->setHtmlId('direct-input')
			->addRule($this->hasSomeText(...), 'Enter the contents of the file, like at least one letter')
			->addRule($this->isWithinMaxBytes(...), 'The security.txt file is too large, paste at most %d bytes', self::MAX_BYTES)
			->setRequired('Enter the contents of the security.txt file');
		$form->addSubmit('validate', 'Validate');
		$form->onSuccess[] = function (Form $form) use ($onSuccess): void {
			$values = $form->getValues();
			assert(is_string($values->input));
			$onSuccess($values->input);
		};
		return $form;
	}


	private function hasSomeText(Control $input): bool
	{
		$value = $input->getValue();
		return is_string($value) && trim($value) !== '';
	}


	private function isWithinMaxBytes(Control $input): bool
	{
		$value = $input->getValue();
		return is_string($value) && strlen($value) <= self::MAX_BYTES;
	}

}
