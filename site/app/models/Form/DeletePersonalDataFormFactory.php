<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Training\Trainings;
use Nette\Application\UI\Form;

class DeletePersonalDataFormFactory
{

	/** @var FormFactory */
	private $factory;

	/** @var Trainings */
	private $trainings;


	public function __construct(FormFactory $factory, Trainings $trainings)
	{
		$this->factory = $factory;
		$this->trainings = $trainings;
	}


	public function create(callable $onSuccess): Form
	{
		$form = $this->factory->create();
		$form->addSubmit('delete', 'Smazat osobní údaje');
		$form->onSuccess[] = function () use ($onSuccess): void {
			$this->trainings->deleteHistoricalPersonalData();
			$onSuccess();
		};

		return $form;
	}

}
