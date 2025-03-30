<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\Applications\TrainingApplications;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatuses;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;

final readonly class TrainingApplicationStatusesFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private TrainingControlsFactory $trainingControlsFactory,
		private TrainingApplications $trainingApplications,
		private TrainingApplicationStatuses $trainingApplicationStatuses,
	) {
	}


	/**
	 * @param callable(Html|null): void $onSuccess
	 * @param list<TrainingApplication> $applications
	 */
	public function create(callable $onSuccess, array $applications): UiForm
	{
		$form = $this->factory->create();
		$container = $form->addContainer('applications');
		foreach ($applications as $application) {
			$statuses = [];
			foreach ($application->getChildrenStatuses() as $status) {
				$statuses[$status->value] = $status->value;
			}
			$select = $container->addSelect((string)$application->getId(), 'Status')
				->setPrompt('- změnit na -')
				->setItems($statuses);
			if ($application->getChildrenStatuses() === []) {
				$select->setPrompt('nelze dále měnit')
					->setDisabled();
			}
		}
		$this->trainingControlsFactory->addStatusDate($form->addText('date', 'Datum:'), true);
		$submitStatuses = $form->addSubmit('submit', 'Změnit');
		$submitFamiliar = $form->addSubmit('familiar', 'Tykat všem')->setValidationScope([]);

		$submitStatuses->onClick[] = function () use ($form, $onSuccess): void {
			$values = $form->getFormValues();
			assert($values->applications instanceof ArrayHash);
			assert(is_string($values->date));
			foreach ($values->applications as $id => $status) {
				assert(is_string($status));
				if ($status !== '') {
					$this->trainingApplicationStatuses->updateStatus($id, TrainingApplicationStatus::from($status), $values->date);
				}
			}
			$onSuccess(null);
		};
		$submitFamiliar->onClick[] = function () use ($form, $onSuccess): void {
			$attendedStatuses = $this->trainingApplicationStatuses->getAttendedStatuses();
			$total = 0;
			foreach (array_keys((array)$form->getUntrustedFormValues()->applications) as $id) {
				$application = $this->trainingApplications->getApplicationById($id);
				if (in_array($application->getStatus(), $attendedStatuses, true) && !$application->isFamiliar()) {
					$this->trainingApplications->setFamiliar($id);
					$total++;
				}
			}

			$statuses = [];
			foreach ($attendedStatuses as $status) {
				$statuses[] = Html::el('code')->setText($status->value);
			}
			$message = Html::el()
				->setText('Tykání nastaveno pro ' . $total . ' účastníků ve stavu ')
				->addHtml(implode(', ', $statuses));
			$onSuccess($message);
		};

		return $form;
	}

}
