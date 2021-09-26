<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Nette\ComponentModel\IContainer;

class TrainingApplicationMultiple extends ProtectedForm
{

	/**
	 * @param IContainer $parent
	 * @param string $name
	 * @param int $count
	 * @param string[] $statuses
	 * @param TrainingControlsFactory $trainingControlsFactory
	 */
	public function __construct(
		IContainer $parent,
		string $name,
		int $count,
		array $statuses,
		TrainingControlsFactory $trainingControlsFactory
	) {
		parent::__construct($parent, $name);

		$applicationsContainer = $this->addContainer('applications');
		for ($i = 0; $i < $count; $i++) {
			$dataContainer = $applicationsContainer->addContainer($i);
			$trainingControlsFactory->addAttendee($dataContainer);
			$trainingControlsFactory->addCompany($dataContainer);
			$trainingControlsFactory->addNote($dataContainer);
			$dataContainer->getComponent('name')->caption = 'Jméno:';
			$dataContainer->getComponent('company')->caption = 'Společnost:';
			$dataContainer->getComponent('street')->caption = 'Ulice:';
		}

		$trainingControlsFactory->addCountry($this);
		$trainingControlsFactory->addStatusDate($this, 'date', 'Datum:', true);
		$this->addSelect('status', 'Status:', $statuses)
			->setRequired('Vyberte status')
			->setPrompt('- vyberte status -');
		$trainingControlsFactory->addSource($this)
			->setPrompt('- vyberte zdroj -');

		$this->addSubmit('submit', 'Přidat');
	}

}
