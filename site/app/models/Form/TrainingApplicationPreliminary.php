<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

class TrainingApplicationPreliminary extends ProtectedForm
{

	use Controls\TrainingAttendee;

	/** @var \Nette\Localization\ITranslator */
	protected $translator;


	public function __construct(\Nette\ComponentModel\IContainer $parent, string $name, \Nette\Localization\ITranslator $translator)
	{
		parent::__construct($parent, $name);
		$this->translator = $translator;

		$this->addAttendee($this);

		$this->addSubmit('signUp', 'Odeslat');
	}

}
