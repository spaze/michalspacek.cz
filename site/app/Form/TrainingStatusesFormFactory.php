<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\Applications\TrainingApplications;
use MichalSpacekCz\Training\Statuses;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Html;

class TrainingStatusesFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly TrainingControlsFactory $trainingControlsFactory,
		private readonly TrainingApplications $trainingApplications,
		private readonly Statuses $trainingStatuses,
		private readonly FormValues $formValues,
	) {
	}


	/**
	 * @param callable(Html|null): void $onSuccess
	 * @param list<TrainingApplication> $applications
	 * @return Form
	 */
	public function create(callable $onSuccess, array $applications): Form
	{
		$form = $this->factory->create();
		$container = $form->addContainer('applications');
		foreach ($applications as $application) {
			$select = $container->addSelect((string)$application->getId(), 'Status')
				->setPrompt('- změnit na -')
				->setItems($application->getChildrenStatuses(), false);
			if (empty($application->getChildrenStatuses())) {
				$select->setDisabled()
					->setPrompt('nelze dále měnit');
			}
		}
		$this->trainingControlsFactory->addStatusDate($form->addText('date', 'Datum:'), true);
		$submitStatuses = $form->addSubmit('submit', 'Změnit');
		$submitFamiliar = $form->addSubmit('familiar', 'Tykat všem')->setValidationScope([]);

		$submitStatuses->onClick[] = function (SubmitButton $button) use ($onSuccess): void {
			$values = $this->formValues->getValues($button);
			foreach ($values->applications as $id => $status) {
				if ($status) {
					$this->trainingStatuses->updateStatus($id, $status, $values->date);
				}
			}
			$onSuccess(null);
		};
		$submitFamiliar->onClick[] = function (SubmitButton $button) use ($onSuccess): void {
			$attendedStatuses = $this->trainingStatuses->getAttendedStatuses();
			$total = 0;
			foreach (array_keys((array)$this->formValues->getUntrustedValues($button)->applications) as $id) {
				$application = $this->trainingApplications->getApplicationById($id);
				if (in_array($application->getStatus(), $attendedStatuses) && !$application->isFamiliar()) {
					$this->trainingApplications->setFamiliar($id);
					$total++;
				}
			}

			$statuses = [];
			foreach ($attendedStatuses as $status) {
				$statuses[] = Html::el('code')->setText($status);
			}
			$message = Html::el()
				->setText('Tykání nastaveno pro ' . $total . ' účastníků ve stavu ')
				->addHtml(implode(', ', $statuses));
			$onSuccess($message);
		};

		return $form;
	}

}
