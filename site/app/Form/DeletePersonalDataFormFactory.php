<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Exception;
use MichalSpacekCz\Training\Files\TrainingFiles;
use MichalSpacekCz\Training\Trainings;
use Nette\Application\UI\Form;
use Nette\Database\Explorer;
use Tracy\Debugger;

class DeletePersonalDataFormFactory
{

	private Explorer $database;

	private FormFactory $factory;

	private Trainings $trainings;

	private TrainingFiles $files;


	public function __construct(Explorer $context, FormFactory $factory, Trainings $trainings, TrainingFiles $files)
	{
		$this->database = $context;
		$this->factory = $factory;
		$this->trainings = $trainings;
		$this->files = $files;
	}


	public function create(callable $onSuccess): Form
	{
		$form = $this->factory->create();
		$form->addSubmit('delete', 'Smazat osobní údaje');
		$form->onSuccess[] = function (Form $form) use ($onSuccess): void {
			$this->database->beginTransaction();
			try {
				$pastIds = array_keys($this->trainings->getPastWithPersonalData());
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
