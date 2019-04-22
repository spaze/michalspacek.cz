<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use Nette\Forms\Controls\TextInput;

trait PaidDate
{

	use Date;

	/**
	 * Adds paid date input control to the form.
	 * @param string $name
	 * @param string $label
	 * @param boolean $required
	 * @return TextInput
	 */
	protected function addPaidDate($name, $label = null, $required = false): TextInput
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
