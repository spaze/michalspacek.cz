<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Exceptions\SpammyApplicationException;
use MichalSpacekCz\Training\FormSpam;
use Nette\Application\UI\Form;
use stdClass;
use Tracy\Debugger;

class TrainingApplicationPreliminaryFactory
{

	private FormFactory $factory;
	private TrainingControlsFactory $trainingControlsFactory;
	private Applications $trainingApplications;
	private FormSpam $formSpam;


	public function __construct(FormFactory $factory, TrainingControlsFactory $trainingControlsFactory, Applications $trainingApplications, FormSpam $formSpam)
	{
		$this->factory = $factory;
		$this->trainingControlsFactory = $trainingControlsFactory;
		$this->trainingApplications = $trainingApplications;
		$this->formSpam = $formSpam;
	}


	public function create(callable $onSuccess, callable $onError, int $trainingId, string $action): Form
	{
		$form = $this->factory->create();
		$this->trainingControlsFactory->addAttendee($form);
		$form->addSubmit('signUp', 'Odeslat');
		$form->onSuccess[] = function (Form $form, stdClass $values) use ($onSuccess, $onError, $trainingId, $action): void {
			try {
				$this->formSpam->check($values, $action);
				$this->trainingApplications->addPreliminaryInvitation($trainingId, $values->name, $values->email);
				$onSuccess($action);
			} catch (SpammyApplicationException $e) {
				Debugger::log($e);
				$onError('messages.trainings.spammyapplication');
			}
		};
		return $form;
	}

}
