<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Training\Applications\TrainingApplications;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatusHistory;
use MichalSpacekCz\Training\Preliminary\PreliminaryTraining;
use MichalSpacekCz\Utils\Arrays;
use Nette\Database\Explorer;
use Throwable;
use Tracy\Debugger;

final readonly class TrainingPreliminaryApplicationsFormFactory
{

	public function __construct(
		private Explorer $database,
		private FormFactory $factory,
		private TrainingApplications $trainingApplications,
		private TrainingApplicationStatusHistory $statusHistory,
	) {
	}


	/**
	 * @param list<PreliminaryTraining> $trainings
	 * @param callable(): void $onSuccess
	 */
	public function create(array $trainings, callable $onSuccess): UiForm
	{
		$form = $this->factory->create();
		$container = $form->addContainer('applications');
		foreach ($trainings as $training) {
			foreach ($training->getApplications() as $application) {
				$container->addCheckbox((string)$application->getId());
			}
		}
		$form->addSubmit('delete', 'Delete');
		$form->onSuccess[] = function () use ($form, $onSuccess): void {
			$selected = array_keys(Arrays::filterEmpty((array)$form->getFormValues()->applications));
			if ($selected !== []) {
				$this->database->beginTransaction();
				try {
					$this->statusHistory->deleteAllHistoryRecordsMultiple($selected);
					$this->trainingApplications->deleteMultiple($selected);
					$this->database->commit();
					$onSuccess();
				} catch (Throwable $e) {
					Debugger::log($e);
					$this->database->rollBack();
					$form->addError('Oops, something went wrong, please try again in a moment');
				}
			}
		};
		return $form;
	}

}
