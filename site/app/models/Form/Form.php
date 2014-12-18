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
	 * Adds paid date input control to the form.
	 * @param string control name
	 * @param string label
	 * @param boolean required
	 * @param integer width of the control (deprecated)
	 * @param integer maximum number of characters the user may enter
	 * @return Nette\Forms\Controls\TextInput
	 */
	public function addPaidDate($name, $label = null, $required = false, $cols = null, $maxLength = null)
	{
		$text = $this->addText($name, $label, $cols, $maxLength);
		if ($required) {
			$text->setRequired('Zadejte datum');
		}
		$text->setAttribute('placeholder', 'YYYY-MM-DD nebo YYYY-MM-DD HH:MM:SS nebo DD.MM.YYYY nebo NOW')
			->setAttribute('title', 'Formát YYYY-MM-DD nebo YYYY-MM-DD HH:MM:SS nebo DD.MM.YYYY nebo NOW')
			->addCondition(self::FILLED)
			->addRule(self::PATTERN, 'Datum musí být ve formátu YYYY-MM-DD nebo YYYY-MM-DD HH:MM:SS nebo DD.MM.YYYY nebo NOW', '((\d{4}-\d{1,2}-\d{1,2})( \d{1,2}:\d{2}:\d{2})?)|(\d{1,2}\.\d{1,2}\.\d{4})|[Nn][Oo][Ww]');
		return $text;
	}

}
