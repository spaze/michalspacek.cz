<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Training\Applications;
use Nette\Application\UI\Form;
use stdClass;

class TrainingApplicationPreliminaryFactory
{

	private FormFactory $factory;
	private TrainingControlsFactory $trainingControlsFactory;
	private Applications $trainingApplications;


	public function __construct(FormFactory $factory, TrainingControlsFactory $trainingControlsFactory, Applications $trainingApplications)
	{
		$this->factory = $factory;
		$this->trainingControlsFactory = $trainingControlsFactory;
		$this->trainingApplications = $trainingApplications;
	}


	public function create(callable $onSuccess, int $trainingId): Form
	{
		$form = $this->factory->create();
		$this->trainingControlsFactory->addAttendee($form);
		$form->addSubmit('signUp', 'Odeslat');
		$form->onSuccess[] = function (Form $form, stdClass $values) use ($onSuccess, $trainingId): void {
			$this->trainingApplications->addPreliminaryInvitation($trainingId, $values->name, $values->email);
			$onSuccess();
		};
		return $form;
	}

}
