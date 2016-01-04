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
	 * @return \Nette\Forms\Controls\TextInput
	 */
	protected function addDate($name, $label = null, $required = false, $cols = null, $maxLength = null, $format = null, $pattern = null)
	{
		$text = $this->addText($name, $label, $cols, $maxLength);
		if ($required) {
			$text->setRequired('Zadejte datum');
		}
		$text->setAttribute('placeholder', $format)
			->setAttribute('title', "Formát {$format}")
			->addCondition(self::FILLED)
			->addRule(Self::PATTERN, "Datum musí být ve formátu {$format}", $pattern);
		return $text;
	}

}
