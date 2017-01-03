<?php
namespace MichalSpacekCz\Form;

/**
 * Abstract form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
abstract class Form extends \Nette\Application\UI\Form
{

	/**
	 * Adds date input control to the form.
	 * @param string $name control name
	 * @param string $label label
	 * @param boolean $required required
	 * @param integer $cols width of the control (deprecated)
	 * @param integer $maxLength maximum number of characters the user may enter
	 * @param string $format Format for the title attribute
	 * @param string $pattern Validation pattern
	 * @param \Nette\Forms\Container|null container
	 * @return \Nette\Forms\Controls\TextInput
	 */
	protected function addDate($name, $label = null, $required = false, $cols = null, $maxLength = null, $format = null, $pattern = null, $container = null)
	{
		return ($container === null ? $this : $container)->addText($name, $label, $cols, $maxLength)
			->setAttribute('placeholder', $format)
			->setAttribute('title', "Formát {$format}")
			->setRequired($required ? 'Zadejte datum' : false)
			->addRule(self::PATTERN, "Datum musí být ve formátu {$format}", $pattern);
	}


	/**
	 * Adds paid date input control to the form.
	 * @param string control name
	 * @param string label
	 * @param boolean required
	 * @param integer width of the control (deprecated)
	 * @param integer maximum number of characters the user may enter
	 * @return \Nette\Forms\Controls\TextInput
	 */
	protected function addPaidDate($name, $label = null, $required = false, $cols = null, $maxLength = null)
	{
		return $this->addDate(
			$name,
			$label,
			$required,
			$cols,
			$maxLength,
			'YYYY-MM-DD nebo YYYY-MM-DD HH:MM:SS nebo DD.MM.YYYY nebo NOW',
			'((\d{4}-\d{1,2}-\d{1,2})( \d{1,2}:\d{2}:\d{2})?)|(\d{1,2}\.\d{1,2}\.\d{4})|[Nn][Oo][Ww]'
		);
	}

}
