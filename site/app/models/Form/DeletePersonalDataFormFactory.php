<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Exception;
use MichalSpacekCz\Training\Files;
use MichalSpacekCz\Training\Trainings;
use Nette\Application\UI\Form;
use Nette\Database\Context;
use Tracy\Debugger;

class DeletePersonalDataFormFactory
{

	private Context $database;

	/** @var FormFactory */
	private $factory;

	/** @var Trainings */
	private $trainings;

	private Files $files;


	public function __construct(Context $context, FormFactory $factory, Trainings $trainings, Files $files)
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
				$onSuccess();
			} catch (Exception $e) {
				$this->database->rollBack();
				$form->addError('Oops, something went wrong, please try again in a moment');
				Debugger::log($e);
			}
		};

		return $form;
	}

}
