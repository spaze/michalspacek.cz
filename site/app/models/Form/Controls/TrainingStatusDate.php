<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

/**
 * Training status date trait.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
trait TrainingStatusDate
{

	use Date;

	/**
	 * Adds status date input control to the form.
	 * @param string $name
	 * @param string $label
	 * @param boolean $required
	 * @return \Nette\Forms\Controls\TextInput
	 */
	protected function addStatusDate($name, $label = null, $required = false): \Nette\Forms\Controls\TextInput
	{
		return $this->addDate(
			$name,
			$label,
			$required,
			'YYYY-MM-DD HH:MM:SS nebo DD.MM.YYYY HH:MM:SS nebo NOW',
			'(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2}:\d{2})|[Nn][Oo][Ww]'
		);
	}

}
