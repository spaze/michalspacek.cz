<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Nette\ComponentModel\IContainer;

class TrainingApplicationPreliminary extends ProtectedForm
{

	public function __construct(IContainer $parent, string $name, TrainingControlsFactory $trainingControlsFactory)
	{
		parent::__construct($parent, $name);
		$trainingControlsFactory->addAttendee($this);
		$this->addSubmit('signUp', 'Odeslat');
	}

}
