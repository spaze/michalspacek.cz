<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Training\Applications\TrainingApplicationStorage;
use MichalSpacekCz\Training\Exceptions\SpammyApplicationException;
use MichalSpacekCz\Training\FormSpam;
use Nette\Application\UI\Form;

class TrainingApplicationPreliminaryFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly TrainingControlsFactory $trainingControlsFactory,
		private readonly TrainingApplicationStorage $trainingApplicationStorage,
		private readonly FormSpam $formSpam,
	) {
	}


	public function create(callable $onSuccess, callable $onError, int $trainingId, string $action): Form
	{
		$form = $this->factory->create();
		$this->trainingControlsFactory->addAttendee($form);
		$form->addSubmit('signUp', 'Odeslat');
		$form->onSuccess[] = function (Form $form) use ($onSuccess, $onError, $trainingId, $action): void {
			$values = $form->getValues();
			try {
				$this->formSpam->check($values);
				$this->trainingApplicationStorage->addPreliminaryInvitation($trainingId, $values->name, $values->email);
				$onSuccess($action);
			} catch (SpammyApplicationException) {
				$onError('messages.trainings.spammyapplication');
			}
		};
		return $form;
	}

}
