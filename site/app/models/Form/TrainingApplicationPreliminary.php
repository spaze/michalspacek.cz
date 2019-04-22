<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingAttendee;
use Nette\ComponentModel\IContainer;
use Nette\Localization\ITranslator;

class TrainingApplicationPreliminary extends ProtectedForm
{

	use TrainingAttendee;

	/** @var ITranslator */
	protected $translator;


	public function __construct(IContainer $parent, string $name, ITranslator $translator)
	{
		parent::__construct($parent, $name);
		$this->translator = $translator;

		$this->addAttendee($this);

		$this->addSubmit('signUp', 'Odeslat');
	}

}
