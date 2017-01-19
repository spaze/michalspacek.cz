<?php
namespace MichalSpacekCz\Form\Controls;

/**
 * Date trait.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
trait Date
{

	/**
	 * Adds date input control to the form.
	 * @param string $name control name
	 * @param string $label label
	 * @param boolean $required required
	 * @param string $format Format for the title attribute
	 * @param string $pattern Validation pattern
	 * @param \Nette\Forms\Container|null container
	 * @return \Nette\Forms\Controls\TextInput
	 */
	protected function addDate($name, $label = null, $required = false, $format = null, $pattern = null, $container = null)
	{
		return ($container === null ? $this : $container)->addText($name, $label)
			->setAttribute('placeholder', $format)
			->setAttribute('title', "Formát {$format}")
			->setRequired($required ? 'Zadejte datum' : false)
			->addRule(self::PATTERN, "Datum musí být ve formátu {$format}", $pattern);
	}

}
