<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Form;

use MichalSpacekCz\Form\UiForm;
use Nette\Forms\Controls\TextInput;

class FormComponents
{

	public function setValue(UiForm $form, string $component, string $value): void
	{
		$field = $form->getComponent($component);
		assert($field instanceof TextInput);
		$field->setDefaultValue($value);
	}

}
