<?php
namespace AdminModule;

use \MichalSpacekCz\Training;

/**
 * Trainings presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Training\Applications */
	protected $trainingApplications;

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;

	/** @var \MichalSpacekCz\Training\Statuses */
	protected $trainingStatuses;

	/** @var \MichalSpacekCz\Training\Trainings */
	protected $trainings;

	/** @var \MichalSpacekCz\Training\Venues */
	protected $trainingVenues;

	/** @var \MichalSpacekCz\Training\Files */
	protected $trainingFiles;

	/** @var array */
	private $dates;

	/** @var array */
	private $applications;

	/** @var array */
	private $applicationIdsAttended;

	/** @var \Nette\Database\Row */
	private $application;

	private $applicationId;

	/** @var \Nette\Database\Row */
	private $review;

	/** @var \Nette\Database\Row */
	private $training;

	private $dateId;

	private $redirectParam;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\Training\Applications $trainingApplications
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 * @param \MichalSpacekCz\Training\Statuses $trainingStatuses
	 * @param \MichalSpacekCz\Training\Trainings $trainings
	 * @param \MichalSpacekCz\Training\Venues $trainingVenues
	 * @param \MichalSpacekCz\Training\Files $trainingFiles
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		Training\Applications $trainingApplications,
		Training\Dates $trainingDates,
		Training\Statuses $trainingStatuses,
		Training\Trainings $trainings,
		Training\Venues $trainingVenues,
		Training\Files $trainingFiles
	)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingDates = $trainingDates;
		$this->trainingStatuses = $trainingStatuses;
		$this->trainings = $trainings;
		$this->trainingVenues = $trainingVenues;
		$this->trainingFiles = $trainingFiles;
		parent::__construct($translator);
	}


	public function actionDate($param)
	{
		$this->dateId = $param;
		$this->redirectParam = $this->dateId;
		$this->training = $this->trainingDates->get($this->dateId);
		$this->applications = $this->trainingApplications->getByDate($this->dateId);
		$attendedStatuses = $this->trainingStatuses->getAttendedStatuses();
		$discardedStatuses = $this->trainingStatuses->getDiscardedStatuses();
		foreach ($this->applications as $application) {
			$application->discarded = in_array($application->status, $discardedStatuses);
			$application->attended = in_array($application->status, $attendedStatuses);
			if ($application->attended) {
				$this->applicationIdsAttended[] = $application->id;
			}
			$application->childrenStatuses = $this->trainingStatuses->getChildrenStatuses($application->status);
		}

		$this->template->pageTitle     = 'Účastníci';
		$this->template->trainingStart = $this->training->start;
		$this->template->trainingName  = $this->training->name;
		$this->template->venueCity     = $this->training->venueCity;
		$this->template->venueName     = $this->training->venueName;
		$this->template->public        = $this->training->public;
		$this->template->applications  = $this->applications;
		$this->template->attendedStatuses = $attendedStatuses;
	}


	public function actionFiles($param)
	{
		$this->applicationId = $param;
		$this->redirectParam = $this->applicationId;
		$application = $this->trainingApplications->getApplicationById($this->applicationId);
		if (!in_array($application->status, $this->trainingStatuses->getAttendedStatuses())) {
			$this->redirect('date', $application->dateId);
		}

		$this->applicationIdsAttended = array($application->applicationId);

		$files = $this->trainingFiles->getFiles($this->applicationId);
		foreach ($files as $file) {
			$file->exists = file_exists("{$file->dirName}/{$file->fileName}");
		}

		$this->training = $this->trainingDates->get($application->dateId);

		$this->template->pageTitle = 'Soubory';
		$this->template->files     = $files;
		$this->template->trainingStart = $this->training->start;
		$this->template->trainingName  = $this->training->name;
		$this->template->trainingCity  = $this->training->venueCity;
		$this->template->name          = $application->name;
		$this->template->dateId        = $application->dateId;
	}


	public function actionReview($param)
	{
		$this->applicationId = $param;
		$this->review = $this->trainings->getReviewByApplicationId($this->applicationId);

		$date = $this->trainingDates->get($this->review->dateId);

		$this->template->pageTitle          = 'Ohlasy';
		$this->template->applicationName    = $this->review->applicationName;
		$this->template->applicationCompany = $this->review->applicationCompany;
		$this->template->trainingStart      = $date->start;
		$this->template->trainingName       = $date->name;
		$this->template->trainingCity  = $date->venueCity;
		$this->template->name          = $this->review->applicationName;
		$this->template->dateId        = $this->review->dateId;
	}


	public function actionApplication($param)
	{
		$this->applicationId = $param;
		$this->application = $this->trainingApplications->getApplicationById($this->applicationId);
		$this->dateId = $this->application->dateId;
		$this->training = $this->trainingDates->get($this->application->dateId);

		$this->template->pageTitle     = "{$this->application->name}";
		$this->template->applicationId = $this->applicationId;
		$this->template->dateId        = $this->dateId;
		$this->template->status        = $this->application->status;
		$this->template->statusTime    = $this->application->statusTime;
		$this->template->trainingName  = $this->training->name;
		$this->template->trainingStart = $this->training->start;
		$this->template->trainingCity  = $this->training->venueCity;
		$this->template->attended      = in_array($this->application->status, $this->trainingStatuses->getAttendedStatuses());
	}


	public function renderDefault()
	{
		$trainings = $this->trainings->getAllTrainings();
		foreach ($trainings as $training) {
			$training->applications = $this->trainingApplications->getValidByDate($training->dateId);
		}

		$this->template->pageTitle = 'Školení';
		$this->template->trainings = $trainings;
		$this->template->now = new \DateTime();
		$this->template->upcomingIds = $this->trainingDates->getPublicUpcomingIds();
	}


	protected function createComponentStatuses($formName)
	{
		$form = new \MichalSpacekCz\Form\TrainingStatuses($this, $formName, $this->applications);
		$form->onSuccess[] = $this->submittedStatuses;
		return $form;
	}


	public function submittedStatuses(\MichalSpacekCz\Form\TrainingStatuses $form)
	{
		$values = $form->getValues();
		foreach ($values->applications as $id => $status) {
			if ($status) {
				$this->trainingStatuses->updateStatus($id, $status, $values->date);
			}
		}
		$this->redirect($this->getAction(), $this->dateId);
	}


	protected function createComponentApplications($formName)
	{
		$sources = array();
		foreach ($this->trainingApplications->getTrainingApplicationSources() as $source) {
			$sources[$source->alias] = $source->name;
		}

		$statuses = array();
		foreach ($this->trainingStatuses->getInitialStatuses() as $status) {
			$statuses[$status] = $status;
		}

		$count = (isset($_POST['applications']) ? count($_POST['applications']) : 1);
		$form = new \MichalSpacekCz\Form\TrainingApplicationMultiple($this, $formName, $count, $sources, $statuses);
		$form->onSuccess[] = $this->submittedApplications;
		return $form;
	}


	private function findDate($dateId)
	{
		foreach ($this->dates as $training) {
			foreach ($training->dates as $date) {
				if ($date->dateId == $dateId) {
					return $date;
				}
			}
		}
		return false;
	}


	public function submittedApplications(\MichalSpacekCz\Form\TrainingApplicationMultiple $form)
	{
		$values = $form->getValues();
		foreach ($values->applications as $application) {
			$this->trainingApplications->insertApplication(
				$this->training,
				$this->dateId,
				$application->name,
				$application->email,
				$application->company,
				$application->street,
				$application->city,
				$application->zip,
				$values->country,
				$application->companyId,
				$application->companyTaxId,
				$application->note,
				$values->status,
				$values->source,
				$values->date
			);
		}
		$this->redirect($this->getAction(), $this->dateId);
	}


	protected function createComponentReview($formName)
	{
		$form = new \MichalSpacekCz\Form\TrainingReview($this, $formName);
		$form->setReview($this->review);
		$form->onSuccess[] = $this->submittedReview;
		return $form;
	}


	public function submittedReview(\MichalSpacekCz\Form\TrainingReview $form)
	{
		$values = $form->getValues();

		$this->trainings->addUpdateReview(
			$this->applicationId,
			$values->overwriteName ? $values->name : null,
			$values->overwriteCompany ? $values->company : null,
			$values->review,
			$values->href,
			$values->hidden
		);

		$this->redirect('date', $this->review->dateId);
	}


	protected function createComponentApplication($formName)
	{
		$form = new \MichalSpacekCz\Form\TrainingApplicationAdmin($this, $formName);
		$form->setApplication($this->application);
		$form->onSuccess[] = $this->submittedApplication;
		return $form;
	}


	public function submittedApplication(\MichalSpacekCz\Form\TrainingApplicationAdmin $form)
	{
		$values = $form->getValues();

		$this->trainingApplications->updateApplicationData(
			$this->applicationId,
			$values->name,
			$values->email,
			$values->company,
			$values->street,
			$values->city,
			$values->zip,
			$values->country,
			$values->companyId,
			$values->companyTaxId,
			$values->note,
			$values->price,
			$values->vatRate / 100,
			$values->priceVat,
			$values->discount,
			$values->invoiceId,
			$values->paid,
			$values->familiar
		);
		$this->redirect('date', $this->dateId);
	}


	protected function createComponentFile($formName)
	{
		$form = new \MichalSpacekCz\Form\TrainingFile($this, $formName);
		$form->onSuccess[] = $this->submittedFile;
		return $form;
	}


	public function submittedFile(\MichalSpacekCz\Form\TrainingFile $form)
	{
		$values = $form->getValues();
		if ($values->file->isOk()) {
			$name = $this->trainingApplications->addFile($this->training, $values->file, $this->applicationIdsAttended);
			$this->flashMessage(
				\Nette\Utils\Html::el()->add('Soubor ')
					->add(\Nette\Utils\Html::el('code')->setText($name))
					->add(' byl přidán')
			);
		} else {
			$this->flashMessage('Soubor nebyl vybrán nebo došlo k nějaké chybě při nahrávání', 'error');
		}

		$this->redirect($this->getAction(), $this->redirectParam);
	}


	protected function createComponentDate($formName)
	{
		$form = new \MichalSpacekCz\Form\TrainingDate($this, $formName, $this->trainings, $this->trainingDates, $this->trainingVenues);
		$form->setTrainingDate($this->trainingDates->get($this->dateId));
		$form->onSuccess[] = $this->submittedDate;
		return $form;
	}


	public function submittedDate(\MichalSpacekCz\Form\TrainingDate $form)
	{
		$values = $form->getValues();
		$this->trainingDates->update(
			$this->dateId,
			$values->training,
			$values->venue,
			$values->start,
			$values->end,
			$values->status,
			$values->public,
			$values->cooperation
		);
		$this->flashMessage('Termín upraven');
		$this->redirect($this->getAction(), $this->redirectParam);
	}

}
