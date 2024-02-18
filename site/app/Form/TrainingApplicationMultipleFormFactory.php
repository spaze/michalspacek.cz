<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Http\HttpInput;
use MichalSpacekCz\Training\Applications\TrainingApplicationStorage;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatuses;
use MichalSpacekCz\Training\Dates\TrainingDate;

readonly class TrainingApplicationMultipleFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private TrainingControlsFactory $trainingControlsFactory,
		private TrainingApplicationStorage $trainingApplicationStorage,
		private TrainingApplicationStatuses $trainingApplicationStatuses,
		private HttpInput $httpInput,
	) {
	}


	/**
	 * @param callable(int): void $onSuccess
	 */
	public function create(callable $onSuccess, TrainingDate $trainingDate): UiForm
	{
		$form = $this->factory->create();
		$applicationsContainer = $form->addContainer('applications');
		$applications = $this->httpInput->getPostArray('applications');
		$count = max($applications !== null ? count($applications) : 1, 1);
		for ($i = 0; $i < $count; $i++) {
			$dataContainer = $applicationsContainer->addContainer($i);
			$this->trainingControlsFactory->addAttendee($dataContainer);
			$this->trainingControlsFactory->addCompany($dataContainer);
			$this->trainingControlsFactory->addNote($dataContainer);
		}

		$this->trainingControlsFactory->addCountry($form);
		$this->trainingControlsFactory->addStatusDate($form->addText('date', 'Datum:'), true);

		$statuses = [];
		foreach ($this->trainingApplicationStatuses->getInitialStatuses() as $status) {
			$statuses[$status->value] = $status->value;
		}
		$form->addSelect('status', 'Status:', $statuses)
			->setRequired('Vyberte status')
			->setPrompt('- vyberte status -');
		$this->trainingControlsFactory->addSource($form)
			->setPrompt('- vyberte zdroj -');

		$form->addSubmit('submit', 'Přidat');

		$form->onSuccess[] = function (UiForm $form) use ($trainingDate, $onSuccess): void {
			$values = $form->getFormValues();
			foreach ($values->applications as $application) {
				$this->trainingApplicationStorage->insertApplication(
					$trainingDate->getTrainingId(),
					$trainingDate->getId(),
					$application->name,
					$application->email,
					$application->company,
					$application->street,
					$application->city,
					$application->zip,
					$values->country,
					$application->companyId,
					$application->companyTaxId,
					$application->note,
					$trainingDate->getPrice(),
					$trainingDate->getStudentDiscount(),
					$values->status,
					$values->source,
					$values->date,
				);
			}
			$onSuccess($trainingDate->getId());
		};

		return $form;
	}

}
