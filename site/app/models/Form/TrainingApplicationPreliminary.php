<?php
namespace MichalSpacekCz\Form;

/**
 * Training application form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingApplicationPreliminary extends TrainingForm
{

	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, $name, \Nette\Localization\ITranslator $translator)
	{
		parent::__construct($parent, $name, $translator);
		$this->addProtection('Platnost formuláře vypršela, odešlete jej znovu');

		$this->addAttendee($this);

		$this->addSubmit('signUp', 'Odeslat');
	}

}
