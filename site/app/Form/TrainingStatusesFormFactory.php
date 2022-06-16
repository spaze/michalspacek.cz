<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Statuses;
use Nette\Application\UI\Form;
use Nette\Database\Row;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Html;

class TrainingStatusesFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly TrainingControlsFactory $trainingControlsFactory,
		private readonly Applications $trainingApplications,
		private readonly Statuses $trainingStatuses,
	) {
	}


	/**
	 * @param callable(Html|null): void $onSuccess
	 * @param Row[] $applications
	 * @return Form
	 */
	public function create(callable $onSuccess, array $applications): Form
	{
		$form = $this->factory->create();
		$container = $form->addContainer('applications');
		foreach ($applications as $application) {
			$select = $container->addSelect((string)$application->id, 'Status')
				->setPrompt('- změnit na -')
				->setItems($application->childrenStatuses, false);
			if (empty($application->childrenStatuses)) {
				$select->setDisabled()
					->setPrompt('nelze dále měnit');
			}
		}
		$this->trainingControlsFactory->addStatusDate($form, 'date', 'Datum:', true);
		$submitStatuses = $form->addSubmit('submit', 'Změnit');
		$submitFamiliar = $form->addSubmit('familiar', 'Tykat všem')->setValidationScope([]);

		$submitStatuses->onClick[] = function (SubmitButton $button) use ($onSuccess): void {
			/** @var Form $form If not, InvalidStateException would be thrown */
			$form = $button->getForm();
			$values = $form->getValues();
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
			/** @var Form $form If not, InvalidStateException would be thrown */
			$form = $button->getForm();
			foreach (array_keys((array)$form->getUnsafeValues(null)->applications) as $id) {
				$application = $this->trainingApplications->getApplicationById($id);
				if (in_array($application->status, $attendedStatuses) && !$application->familiar) {
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
