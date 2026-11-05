<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\TrainingApplication;

use MichalSpacekCz\Training\Applications\TrainingApplications;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatusHistory;
use MichalSpacekCz\Training\Preliminary\PreliminaryTraining;
use MichalSpacekCz\Utils\Arrays;
use Nette\Application\UI\Form;
use Nette\Database\Explorer;
use PDOException;
use Tracy\Debugger;

final readonly class TrainingPreliminaryApplicationsListFormFactory
{

	public function __construct(
		private Explorer $database,
		private TrainingApplications $trainingApplications,
		private TrainingApplicationStatusHistory $statusHistory,
	) {
	}


	/**
	 * @param list<PreliminaryTraining> $trainings
	 * @param callable(): void $onSuccess
	 */
	public function create(array $trainings, callable $onSuccess): Form
	{
		$form = new Form();
		$container = $form->addContainer('applications');
		foreach ($trainings as $training) {
			foreach ($training->getApplications() as $application) {
				$container->addCheckbox((string)$application->getId());
			}
		}
		$form->addSubmit('delete', 'Delete');
		$form->onSuccess[] = function () use ($form, $onSuccess): void {
			$selected = array_keys(Arrays::filterEmpty((array)$form->getValues()->applications));
			if ($selected !== []) {
				$this->database->beginTransaction();
				try {
					$this->statusHistory->deleteAllHistoryRecordsMultiple($selected);
					$this->trainingApplications->deleteMultiple($selected);
					$this->database->commit();
					$onSuccess();
				} catch (PDOException $e) {
					Debugger::log($e);
					$this->database->rollBack();
					$form->addError('Oops, something went wrong, please try again in a moment');
				}
			}
		};
		return $form;
	}

}
