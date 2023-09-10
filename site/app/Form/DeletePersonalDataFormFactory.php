<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Exception;
use MichalSpacekCz\Training\Dates\TrainingDates;
use MichalSpacekCz\Training\Files\TrainingFiles;
use MichalSpacekCz\Training\Trainings\Trainings;
use Nette\Database\Explorer;
use Tracy\Debugger;

class DeletePersonalDataFormFactory
{

	public function __construct(
		private readonly Explorer $database,
		private readonly FormFactory $factory,
		private readonly Trainings $trainings,
		private readonly TrainingDates $trainingDates,
		private readonly TrainingFiles $files,
	) {
	}


	public function create(callable $onSuccess): UiForm
	{
		$form = $this->factory->create();
		$form->addSubmit('delete', 'Smazat osobní údaje');
		$form->onSuccess[] = function (UiForm $form) use ($onSuccess): void {
			$this->database->beginTransaction();
			try {
				$pastIds = [];
				foreach ($this->trainingDates->getPastWithPersonalData() as $date) {
					$pastIds[] = $date->getId();
				}
				$this->trainings->deletePersonalData($pastIds);
				$this->files->deleteFiles($pastIds);
				$this->database->commit();
			} catch (Exception $e) {
				Debugger::log($e);
				$this->database->rollBack();
				$form->addError('Oops, something went wrong, please try again in a moment');
			}
			$onSuccess();
		};

		return $form;
	}

}
