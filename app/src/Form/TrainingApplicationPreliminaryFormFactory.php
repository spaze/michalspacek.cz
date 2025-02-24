<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Training\ApplicationForm\TrainingApplicationFormSpam;
use MichalSpacekCz\Training\Applications\TrainingApplicationStorage;
use MichalSpacekCz\Training\Exceptions\SpammyApplicationException;

final readonly class TrainingApplicationPreliminaryFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private TrainingControlsFactory $trainingControlsFactory,
		private TrainingApplicationStorage $trainingApplicationStorage,
		private TrainingApplicationFormSpam $formSpam,
	) {
	}


	public function create(callable $onSuccess, callable $onError, int $trainingId, string $action): UiForm
	{
		$form = $this->factory->create();
		$this->trainingControlsFactory->addAttendee($form);
		$form->addSubmit('signUp', 'Odeslat');
		$form->onSuccess[] = function (UiForm $form) use ($onSuccess, $onError, $trainingId, $action): void {
			$values = $form->getFormValues();
			assert(is_string($values->name));
			assert(is_string($values->email));
			try {
				$this->formSpam->check($values->name);
				$this->trainingApplicationStorage->addPreliminaryInvitation($trainingId, $values->name, $values->email);
				$onSuccess($action);
			} catch (SpammyApplicationException) {
				$onError('messages.trainings.spammyapplication');
			}
		};
		return $form;
	}

}
