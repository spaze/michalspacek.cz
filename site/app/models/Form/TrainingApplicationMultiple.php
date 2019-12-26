<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Training\Applications;
use Nette\ComponentModel\IContainer;

class TrainingApplicationMultiple extends ProtectedForm
{

	/** @var Applications */
	private $trainingApplications;


	/**
	 * @param IContainer $parent
	 * @param string $name
	 * @param integer $count
	 * @param string[] $statuses
	 * @param Applications $trainingApplications
	 * @param TrainingControlsFactory $trainingControlsFactory
	 */
	public function __construct(
		IContainer $parent,
		string $name,
		int $count,
		array $statuses,
		Applications $trainingApplications,
		TrainingControlsFactory $trainingControlsFactory
	) {
		parent::__construct($parent, $name);
		$this->trainingApplications = $trainingApplications;

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
