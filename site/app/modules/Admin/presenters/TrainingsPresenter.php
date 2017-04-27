<?php
namespace App\AdminModule\Presenters;

use MichalSpacekCz\Training;
use Nette\Utils\Html;

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

	/** @var \MichalSpacekCz\Training\Reviews */
	protected $trainingReviews;

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
	 * @param \MichalSpacekCz\Training\Applications $trainingApplications
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 * @param \MichalSpacekCz\Training\Statuses $trainingStatuses
	 * @param \MichalSpacekCz\Training\Trainings $trainings
	 * @param \MichalSpacekCz\Training\Venues $trainingVenues
	 * @param \MichalSpacekCz\Training\Files $trainingFiles
	 * @param \MichalSpacekCz\Training\Reviews $trainingReviews
	 */
	public function __construct(
		Training\Applications $trainingApplications,
		Training\Dates $trainingDates,
		Training\Statuses $trainingStatuses,
		Training\Trainings $trainings,
		Training\Venues $trainingVenues,
		Training\Files $trainingFiles,
		Training\Reviews $trainingReviews
	)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingDates = $trainingDates;
		$this->trainingStatuses = $trainingStatuses;
		$this->trainings = $trainings;
		$this->trainingVenues = $trainingVenues;
		$this->trainingFiles = $trainingFiles;
		$this->trainingReviews = $trainingReviews;
		parent::__construct();
	}


	public function actionDate($param)
	{
		$this->dateId = $param;
		$this->redirectParam = $this->dateId;
		$this->training = $this->trainingDates->get($this->dateId);
		if (!$this->training) {
			throw new \Nette\Application\BadRequestException("Date id {$param} does not exist, yet", \Nette\Http\Response::S404_NOT_FOUND);
		}
		$attendedStatuses = $this->trainingStatuses->getAttendedStatuses();
		$discardedStatuses = $this->trainingStatuses->getDiscardedStatuses();
		$validCount = 0;
		$applications = $discarded = [];
		foreach ($this->trainingApplications->getByDate($this->dateId) as $application) {
			$application->discarded = in_array($application->status, $discardedStatuses);
			if (!$application->discarded) {
				$validCount++;
				$applications[] = $application;
			} else {
				$discarded[] = $application;
			}
			$application->attended = in_array($application->status, $attendedStatuses);
			if ($application->attended) {
				$this->applicationIdsAttended[] = $application->id;
			}
			$application->childrenStatuses = $this->trainingStatuses->getChildrenStatusesForApplicationId($application->status, $application->id);
		}
		$this->applications = array_merge($applications, $discarded);

		$this->template->pageTitle     = 'Účastníci';
		$this->template->trainingStart = $this->training->start;
		$this->template->trainingName  = $this->training->name;
		$this->template->venueCity     = $this->training->venueCity;
		$this->template->venueName     = $this->training->venueName;
		$this->template->public        = $this->training->public;
		$this->template->applications  = $this->applications;
		$this->template->validCount    = $validCount;
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
		$this->training = $this->trainingDates->get($application->dateId);

		$this->template->pageTitle = 'Soubory';
		$this->template->files     = $this->trainingFiles->getFiles($this->applicationId);
		$this->template->trainingStart = $this->training->start;
		$this->template->trainingName  = $this->training->name;
		$this->template->trainingCity  = $this->training->venueCity;
		$this->template->name          = $application->name;
		$this->template->dateId        = $application->dateId;
	}


	public function actionReview($param)
	{
		$this->applicationId = $param;
		$this->review = $this->trainingReviews->getReviewByApplicationId($this->applicationId);

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
		if (isset($this->application->dateId)) {
			$this->dateId = $this->application->dateId;
			$this->training = $this->trainingDates->get($this->dateId);
			$start = $this->training->start;
			$city = $this->training->venueCity;
		} else {
			$this->dateId = $start = $city = null;
			$this->training = $this->trainings->getIncludingCustom($this->application->trainingAction);
		}

		$this->template->pageTitle     = $this->application->name;
		$this->template->applicationId = $this->applicationId;
		$this->template->dateId        = $this->dateId;
		$this->template->status        = $this->application->status;
		$this->template->statusTime    = $this->application->statusTime;
		$this->template->trainingName  = $this->training->name;
		$this->template->trainingStart = $start;
		$this->template->trainingCity  = $city;
		$this->template->sourceName    = $this->application->sourceName;
		$this->template->companyId     = $this->application->companyId;
		$this->template->attended      = in_array($this->application->status, $this->trainingStatuses->getAttendedStatuses());
		$this->template->history       = $this->trainingStatuses->getStatusHistory($this->applicationId);
	}


	public function actionPreliminary($param)
	{
		$this->template->pageTitle = 'Předběžné přihlášky';
		$this->template->preliminaryApplications = $this->trainingApplications->getPreliminary();
		$this->template->upcoming = $this->trainingDates->getPublicUpcoming();
	}


	public function renderDefault()
	{
		$trainings = $this->trainings->getAllTrainings();
		foreach ($trainings as $training) {
			$training->applications = $this->trainingApplications->getValidByDate($training->dateId);
			$training->canceledApplications = $this->trainingApplications->getCanceledPaidByDate($training->dateId);
			$training->validCount = count($training->applications);
			$training->requiresAttention = false;
		}

		$this->template->pageTitle = 'Školení';
		$this->template->trainings = $trainings;
		$this->template->now = new \DateTime();
		$this->template->upcomingIds = $this->trainingDates->getPublicUpcomingIds();
	}


	protected function createComponentStatuses($formName)
	{
		$form = new \MichalSpacekCz\Form\TrainingStatuses($this, $formName, $this->applications, $this->translator);
		$form->getComponent('submit')->onClick[] = [$this, 'submittedStatuses'];
		$form->getComponent('familiar')->onClick[] = [$this, 'submittedFamiliar'];
		return $form;
	}


	public function submittedStatuses(\Nette\Forms\Controls\SubmitButton $button)
	{
		$values = $button->getForm()->getValues();
		foreach ($values->applications as $id => $status) {
			if ($status) {
				$this->trainingStatuses->updateStatus($id, $status, $values->date);
			}
		}
		$this->redirect($this->getAction(), $this->dateId);
	}


	public function submittedFamiliar(\Nette\Forms\Controls\SubmitButton $button)
	{
		$values = $button->getForm()->getValues(true);  // array_keys() does not work with ArrayHash object
		$attendedStatuses = $this->trainingStatuses->getAttendedStatuses();
		$total = 0;
		foreach (array_keys($values['applications']) as $id) {
			$application = $this->trainingApplications->getApplicationById($id);
			if (in_array($application->status, $attendedStatuses) && !$application->familiar) {
				$this->trainingApplications->setFamiliar($id);
				$total++;
			}
		}

		$statuses = array();
		foreach ($attendedStatuses as $status) {
			$statuses[] = Html::el('code')->setText($status);
		}
		$this->flashMessage(
			Html::el()
				->setText('Tykání nastaveno pro ' . $total . ' účastníků ve stavu ')
				->addHtml(implode(', ', $statuses))
		);

		$this->redirect($this->getAction(), $this->dateId);
	}


	protected function createComponentApplications($formName)
	{
		$statuses = array();
		foreach ($this->trainingStatuses->getInitialStatuses() as $status) {
			$statuses[$status] = $status;
		}

		$count = count($this->request->getPost('applications')) ?: 1;
		$form = new \MichalSpacekCz\Form\TrainingApplicationMultiple($this, $formName, $count, $statuses, $this->trainingApplications, $this->translator);
		$form->onSuccess[] = [$this, 'submittedApplications'];
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


	public function submittedApplications(\MichalSpacekCz\Form\TrainingApplicationMultiple $form, $values)
	{
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
		$form->onSuccess[] = [$this, 'submittedReview'];
		return $form;
	}


	public function submittedReview(\MichalSpacekCz\Form\TrainingReview $form, $values)
	{
		$this->trainingReviews->addUpdateReview(
			$this->applicationId,
			$values->overwriteName ? $values->name : null,
			$values->overwriteCompany ? $values->company : null,
			$values->jobTitle ?: null,
			$values->review,
			$values->href,
			$values->hidden
		);

		$this->redirect('date', $this->review->dateId);
	}


	protected function createComponentApplication($formName)
	{
		$form = new \MichalSpacekCz\Form\TrainingApplicationAdmin($this, $formName, $this->trainingApplications, $this->trainingDates, $this->translator);
		$form->setApplication($this->application);
		$form->onSuccess[] = [$this, 'submittedApplication'];
		return $form;
	}


	public function submittedApplication(\MichalSpacekCz\Form\TrainingApplicationAdmin $form, $values)
	{
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
			$values->source,
			$values->price,
			$values->vatRate / 100,
			$values->priceVat,
			$values->discount,
			$values->invoiceId,
			$values->paid,
			$values->familiar,
			(isset($values->date) ? $values->date : false)
		);
		if (isset($this->dateId) || isset($values->date)) {
			$this->redirect('date', $this->dateId);
		} else {
			$this->redirect('preliminary', $this->applicationId);
		}
	}


	protected function createComponentFile($formName)
	{
		$form = new \MichalSpacekCz\Form\TrainingFile($this, $formName);
		$form->onSuccess[] = [$this, 'submittedFile'];
		return $form;
	}


	public function submittedFile(\MichalSpacekCz\Form\TrainingFile $form, $values)
	{
		if ($values->file->isOk()) {
			$name = $this->trainingFiles->addFile($this->training, $values->file, $this->applicationIdsAttended);
			$this->flashMessage(
				Html::el()->setText('Soubor ')
					->addHtml(Html::el('code')->setText($name))
					->addHtml(Html::el()->setText(' byl přidán'))
			);
		} else {
			$this->flashMessage('Soubor nebyl vybrán nebo došlo k nějaké chybě při nahrávání', 'error');
		}

		$this->redirect($this->getAction(), $this->redirectParam);
	}


	protected function createComponentDate($formName)
	{
		$form = new \MichalSpacekCz\Form\TrainingDate($this, $formName, $this->trainings, $this->trainingDates, $this->trainingVenues);
		$form->setTrainingDate($this->training);
		$form->onSuccess[] = [$this, 'submittedDate'];
		return $form;
	}


	public function submittedDate(\MichalSpacekCz\Form\TrainingDate $form, $values)
	{
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


	protected function createComponentAddDate($formName)
	{
		$form = new \MichalSpacekCz\Form\TrainingDate($this, $formName, $this->trainings, $this->trainingDates, $this->trainingVenues);
		$form->onSuccess[] = [$this, 'submittedAddDate'];
		return $form;
	}


	public function submittedAddDate(\MichalSpacekCz\Form\TrainingDate $form, $values)
	{
		$this->trainingDates->add(
			$values->training,
			$values->venue,
			$values->start,
			$values->end,
			$values->status,
			$values->public,
			$values->cooperation
		);
		$this->redirect('Trainings:');
	}

}
