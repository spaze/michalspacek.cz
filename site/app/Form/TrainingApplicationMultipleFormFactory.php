<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Price;
use MichalSpacekCz\Training\Statuses;
use Nette\Application\Request;
use Nette\Application\UI\Form;
use stdClass;

class TrainingApplicationMultipleFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly TrainingControlsFactory $trainingControlsFactory,
		private readonly Applications $trainingApplications,
		private readonly Statuses $trainingStatuses,
	) {
	}


	/**
	 * @param callable(int): void $onSuccess
	 * @param Request $request
	 * @param int $trainingId
	 * @param int|null $dateId
	 * @param Price|null $price
	 * @param int|null $studentDiscount
	 * @return Form
	 */
	public function create(callable $onSuccess, Request $request, int $trainingId, ?int $dateId, ?Price $price, ?int $studentDiscount): Form
	{
		$form = $this->factory->create();
		$applicationsContainer = $form->addContainer('applications');
		$applications = $request->getPost('applications');
		$count = max(is_array($applications) ? count($applications) : 1, 1);
		for ($i = 0; $i < $count; $i++) {
			$dataContainer = $applicationsContainer->addContainer($i);
			$this->trainingControlsFactory->addAttendee($dataContainer);
			$this->trainingControlsFactory->addCompany($dataContainer);
			$this->trainingControlsFactory->addNote($dataContainer);
			$dataContainer->getComponent('name')->caption = 'Jméno:';
			$dataContainer->getComponent('company')->caption = 'Společnost:';
			$dataContainer->getComponent('street')->caption = 'Ulice:';
		}

		$this->trainingControlsFactory->addCountry($form);
		$this->trainingControlsFactory->addStatusDate($form, 'date', 'Datum:', true);

		$statuses = [];
		foreach ($this->trainingStatuses->getInitialStatuses() as $status) {
			$statuses[$status] = $status;
		}
		$form->addSelect('status', 'Status:', $statuses)
			->setRequired('Vyberte status')
			->setPrompt('- vyberte status -');
		$this->trainingControlsFactory->addSource($form)
			->setPrompt('- vyberte zdroj -');

		$form->addSubmit('submit', 'Přidat');

		$form->onSuccess[] = function (Form $form, stdClass $values) use ($trainingId, $dateId, $price, $studentDiscount, $onSuccess): void {
			foreach ($values->applications as $application) {
				$this->trainingApplications->insertApplication(
					$trainingId,
					$dateId,
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
					$price,
					$studentDiscount,
					$values->status,
					$values->source,
					$values->date,
				);
			}
			$onSuccess($dateId);
		};

		return $form;
	}

}
