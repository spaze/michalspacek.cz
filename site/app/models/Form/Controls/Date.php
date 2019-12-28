<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use Nette\Forms\Container;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;

trait Date
{

	protected function addDate(Container $container, string $name, string $label, bool $required, string $format, string $pattern): TextInput
	{
		return $container->addText($name, $label)
			->setHtmlAttribute('placeholder', $format)
			->setHtmlAttribute('title', "Formát {$format}")
			->setRequired($required ? 'Zadejte datum' : false)
			->addRule(Form::PATTERN, "Datum musí být ve formátu {$format}", $pattern);
	}

}
