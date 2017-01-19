<?php
namespace MichalSpacekCz\Form\Controls;

/**
 * Paid date trait.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
trait PaidDate
{

	use Date;

	/**
	 * Adds paid date input control to the form.
	 * @param string control name
	 * @param string label
	 * @param boolean required
	 * @return \Nette\Forms\Controls\TextInput
	 */
	protected function addPaidDate($name, $label = null, $required = false)
	{
		return $this->addDate(
			$name,
			$label,
			$required,
			'YYYY-MM-DD nebo YYYY-MM-DD HH:MM:SS nebo DD.MM.YYYY nebo NOW',
			'((\d{4}-\d{1,2}-\d{1,2})( \d{1,2}:\d{2}:\d{2})?)|(\d{1,2}\.\d{1,2}\.\d{4})|[Nn][Oo][Ww]'
		);
	}

}
