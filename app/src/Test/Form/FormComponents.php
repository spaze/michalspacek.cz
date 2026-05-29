<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Form;

use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;

final class FormComponents
{

	public function setValue(Form $form, string $component, string $value): void
	{
		$field = $form->getComponent($component);
		assert($field instanceof TextInput);
		$field->setDefaultValue($value);
	}

}
