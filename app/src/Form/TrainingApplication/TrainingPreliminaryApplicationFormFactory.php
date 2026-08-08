<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\TrainingApplication;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\Training\ApplicationForm\TrainingApplicationFormSpam;
use MichalSpacekCz\Training\Applications\TrainingApplicationStorage;
use MichalSpacekCz\Training\Exceptions\SpammyApplicationException;
use Nette\Forms\Form;

final readonly class TrainingPreliminaryApplicationFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private TrainingControlsFactory $trainingControlsFactory,
		private TrainingApplicationStorage $trainingApplicationStorage,
		private TrainingApplicationFormSpam $formSpam,
	) {
	}


	public function create(callable $onSuccess, callable $onError, int $trainingId, string $action): Form
	{
		$form = $this->factory->create();
		$this->trainingControlsFactory->addAttendee($form);
		$form->addSubmit('signUp', 'Odeslat');
		$form->onSuccess[] = function (Form $form) use ($onSuccess, $onError, $trainingId, $action): void {
			$values = $form->getValues();
			assert(is_string($values->attendeeName));
			assert(is_string($values->email));
			try {
				$this->formSpam->check($values->attendeeName);
				$this->trainingApplicationStorage->addPreliminaryInvitation($trainingId, $values->attendeeName, $values->email);
				$onSuccess($action);
			} catch (SpammyApplicationException) {
				$onError('messages.trainings.spammyapplication');
			}
		};
		return $form;
	}

}
