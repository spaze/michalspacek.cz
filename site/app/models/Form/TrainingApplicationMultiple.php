<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingAttendee;
use MichalSpacekCz\Form\Controls\TrainingCompany;
use MichalSpacekCz\Form\Controls\TrainingCountry;
use MichalSpacekCz\Form\Controls\TrainingNote;
use MichalSpacekCz\Form\Controls\TrainingSource;
use MichalSpacekCz\Form\Controls\TrainingStatusDate;
use MichalSpacekCz\Training\Applications;
use Nette\ComponentModel\IContainer;
use Nette\Localization\ITranslator;

class TrainingApplicationMultiple extends ProtectedForm
{

	use TrainingAttendee;
	use TrainingCompany;
	use TrainingCountry;
	use TrainingNote;
	use TrainingSource;
	use TrainingStatusDate;

	/** @var Applications */
	protected $trainingApplications;

	/** @var ITranslator */
	protected $translator;


	/**
	 * @param IContainer $parent
	 * @param string $name
	 * @param integer $count
	 * @param string[] $statuses
	 * @param Applications $trainingApplications
	 * @param ITranslator $translator
	 */
	public function __construct(
		IContainer $parent,
		string $name,
		int $count,
		array $statuses,
		Applications $trainingApplications,
		ITranslator $translator
	) {
		parent::__construct($parent, $name);
		$this->trainingApplications = $trainingApplications;
		$this->translator = $translator;

		$applicationsContainer = $this->addContainer('applications');
		for ($i = 0; $i < $count; $i++) {
			$dataContainer = $applicationsContainer->addContainer($i);
			$this->addAttendee($dataContainer);
			$this->addCompany($dataContainer);
			$this->addNote($dataContainer);
			$dataContainer->getComponent('name')->caption = 'Jméno:';
			$dataContainer->getComponent('company')->caption = 'Společnost:';
			$dataContainer->getComponent('street')->caption = 'Ulice:';
		}

		$this->addCountry($this);
		$this->addStatusDate('date', 'Datum:', true);
		$this->addSelect('status', 'Status:', $statuses)
			->setRequired('Vyberte status')
			->setPrompt('- vyberte status -');
		$this->addSource($this)
			->setPrompt('- vyberte zdroj -');

		$this->addSubmit('submit', 'Přidat');
	}

}
