<?php
namespace MichalSpacekCz\Form;

/**
 * Training application form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingApplicationPreliminary extends \Nette\Application\UI\Form
{

	use Controls\TrainingAttendee;

	/** @var \Nette\Localization\ITranslator */
	protected $translator;


	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, $name, \Nette\Localization\ITranslator $translator)
	{
		parent::__construct($parent, $name);
		$this->translator = $translator;
		$this->addProtection('Platnost formuláře vypršela, odešlete jej znovu');

		$this->addAttendee($this);

		$this->addSubmit('signUp', 'Odeslat');
	}

}
