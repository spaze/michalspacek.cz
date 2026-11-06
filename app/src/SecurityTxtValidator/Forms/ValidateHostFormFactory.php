<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Forms;

use Nette\Application\UI\Form;

final class ValidateHostFormFactory
{

	public function create(callable $onSuccess, ?string $host, string $formAction): Form
	{
		$form = new Form();
		$form->setAction($formAction);
		$form->addText('host')
			->setHtmlId('host')
			->setHtmlAttribute('placeholder', 'like example.com')
			->setHtmlAttribute('autocomplete', 'url')
			->setDefaultValue($host)
			->setRequired('Please enter a hostname or a URL')
		;
		$form->addSubmit('check', 'Check');
		$form->onSuccess[] = function (Form $form) use ($onSuccess): void {
			$values = $form->getValues();
			assert(is_string($values->host));
			$host = trim($values->host);
			$onSuccess($host);
		};
		return $form;
	}

}
