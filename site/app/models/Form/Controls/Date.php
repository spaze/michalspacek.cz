<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use Nette\Forms\Container;
use Nette\Forms\Controls\TextInput;

trait Date
{

	/**
	 * Adds date input control to the form.
	 * @param string $name
	 * @param string $label
	 * @param boolean $required
	 * @param string $format Format for the title attribute
	 * @param string $pattern Validation pattern
	 * @param Container|null $container
	 * @return TextInput
	 */
	protected function addDate(string $name, string $label, bool $required, string $format, string $pattern, ?Container $container = null): TextInput
	{
		return ($container === null ? $this : $container)->addText($name, $label)
			->setHtmlAttribute('placeholder', $format)
			->setHtmlAttribute('title', "Formát {$format}")
			->setRequired($required ? 'Zadejte datum' : false)
			->addRule(self::PATTERN, "Datum musí být ve formátu {$format}", $pattern);
	}

}
