<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

final class FormFactory
{

	public function create(): UiForm
	{
		$form = new UiForm();
		$form->addProtection();
		return $form;
	}

}
