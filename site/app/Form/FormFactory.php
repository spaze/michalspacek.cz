<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Nette\Application\UI\Form;

final class FormFactory
{

	public function create(): Form
	{
		$form = new Form();
		$form->addProtection();
		return $form;
	}

}
