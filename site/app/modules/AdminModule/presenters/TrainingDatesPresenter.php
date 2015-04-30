<?php
namespace AdminModule;

use MichalSpacekCz\Training;

/**
 * Trainings dates presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingDatesPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Training\Trainings */
	protected $trainings;

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;

	/** @var \MichalSpacekCz\Training\Venues */
	protected $trainingVenues;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\Training\Trainings $trainings
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 * @param \MichalSpacekCz\Training\Venues $trainingVenues
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		Training\Trainings $trainings,
		Training\Dates $trainingDates,
		Training\Venues $trainingVenues
	)
	{
		$this->trainings = $trainings;
		$this->trainingDates = $trainingDates;
		$this->trainingVenues = $trainingVenues;
		parent::__construct($translator);
	}


	public function renderAdd()
	{
		$this->template->pageTitle = 'Přidat termín';
	}


	protected function createComponentDate($formName)
	{
		$form = new \MichalSpacekCz\Form\TrainingDate($this, $formName, $this->trainings, $this->trainingDates, $this->trainingVenues);
		$form->onSuccess[] = $this->submittedDate;
		return $form;
	}


	public function submittedDate(\MichalSpacekCz\Form\TrainingDate $form)
	{
		$values = $form->getValues();
		$this->trainingDates->add(
			$values->training,
			$values->venue,
			$values->start,
			$values->end,
			$values->status,
			$values->public,
			$values->cooperation
		);
		$this->redirect('Homepage:');
	}

}
