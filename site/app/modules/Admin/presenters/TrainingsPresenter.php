<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use DateTime;
use MichalSpacekCz\Form\DeletePersonalDataFormFactory;
use MichalSpacekCz\Form\TrainingApplicationAdmin;
use MichalSpacekCz\Form\TrainingApplicationMultiple;
use MichalSpacekCz\Form\TrainingDate;
use MichalSpacekCz\Form\TrainingFile;
use MichalSpacekCz\Form\TrainingReview;
use MichalSpacekCz\Form\TrainingStatuses;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Dates;
use MichalSpacekCz\Training\Files;
use MichalSpacekCz\Training\Reviews;
use MichalSpacekCz\Training\Statuses;
use MichalSpacekCz\Training\Trainings;
use MichalSpacekCz\Training\Venues;
use Nette\Application\BadRequestException;
use Nette\Database\Row;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use Netxten\Templating\Helpers;

class TrainingsPresenter extends BasePresenter
{

	/** @var Applications */
	protected $trainingApplications;

	/** @var Dates */
	protected $trainingDates;

	/** @var Statuses */
	protected $trainingStatuses;

	/** @var Trainings */
	protected $trainings;

	/** @var Venues */
	protected $trainingVenues;

	/** @var Files */
	protected $trainingFiles;

	/** @var Reviews */
	protected $trainingReviews;

	/** @var Helpers */
	private $netxtenHelpers;

	/** @var DeletePersonalDataFormFactory */
	private $deletePersonalDataFormFactory;

	/** @var array */
	private $applications;

	/** @var array */
	private $applicationIdsAttended;

	/** @var Row */
	private $application;

	private $applicationId;

	/** @var Row */
	private $review;

	/** @var Row */
	private $training;

	/** @var integer|null */
	private $dateId;

	/** @var integer */
	private $redirectParam;


	public function __construct(
		Applications $trainingApplications,
		Dates $trainingDates,
		Statuses $trainingStatuses,
		Trainings $trainings,
		Venues $trainingVenues,
		Files $trainingFiles,
		Reviews $trainingReviews,
		Helpers $netxtenHelpers,
		DeletePersonalDataFormFactory $deletePersonalDataFormFactory
	)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingDates = $trainingDates;
		$this->trainingStatuses = $trainingStatuses;
		$this->trainings = $trainings;
		$this->trainingVenues = $trainingVenues;
		$this->trainingFiles = $trainingFiles;
		$this->trainingReviews = $trainingReviews;
		$this->netxtenHelpers = $netxtenHelpers;
		$this->deletePersonalDataFormFactory = $deletePersonalDataFormFactory;
		parent::__construct();
	}


	public function actionDate(int $param): void
	{
		$this->dateId = $param;
		$this->redirectParam = $this->dateId;
		$this->training = $this->trainingDates->get($this->dateId);
		if (!$this->training) {
			throw new BadRequestException("Date id {$param} does not exist, yet");
		}
		$validCount = 0;
		$applications = $discarded = [];
		foreach ($this->trainingApplications->getByDate($this->dateId) as $application) {
			if (!$application->discarded) {
				$validCount++;
				$applications[] = $application;
			} else {
				$discarded[] = $application;
			}
			if ($application->attended) {
				$this->applicationIdsAttended[] = $application->id;
			}
			$application->childrenStatuses = $this->trainingStatuses->getChildrenStatusesForApplicationId($application->status, $application->id);
		}
		$this->applications = array_merge($applications, $discarded);

		$this->template->pageTitle     = 'Účastníci';
		$this->template->trainingStart = $this->training->start;
		$this->template->trainingEnd   = $this->training->end;
		$this->template->trainingName  = $this->training->name;
		$this->template->venueCity     = $this->training->venueCity;
		$this->template->venueName     = $this->training->venueName;
		$this->template->public        = $this->training->public;
		$this->template->note          = $this->training->note;
		$this->template->applications  = $this->applications;
		$this->template->validCount    = $validCount;
		$this->template->attendedStatuses = $this->trainingStatuses->getAttendedStatuses();
		$this->template->reviews = $this->trainingReviews->getReviewsByDateId($this->dateId);
	}


	public function actionFiles(int $param): void
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
		$this->template->trainingEnd = $this->training->end;
		$this->template->trainingName  = $this->training->name;
		$this->template->trainingCity  = $this->training->venueCity;
		$this->template->name          = $application->name;
		$this->template->dateId        = $application->dateId;
	}


	public function actionReview(int $param): void
	{
		$this->review = $this->trainingReviews->getReview($param);

		$date = $this->trainingDates->get($this->review->dateId);

		$this->template->pageTitle = "Ohlas od {$this->review->name}" . ($this->review->company ? ", {$this->review->company}": '');
		$this->template->trainingStart      = $date->start;
		$this->template->trainingEnd = $date->end;
		$this->template->trainingName       = $date->name;
		$this->template->trainingCity  = $date->venueCity;
		$this->template->name          = $this->review->name;
		$this->template->dateId        = $this->review->dateId;
	}


	public function actionApplication(int $param): void
	{
		$this->applicationId = $param;
		$this->application = $this->trainingApplications->getApplicationById($this->applicationId);
		if (!$this->application) {
			throw new BadRequestException("No application with id {$this->applicationId}");
		}

		if (isset($this->application->dateId)) {
			$this->dateId = $this->application->dateId;
			$this->training = $this->trainingDates->get($this->dateId);
			$start = $this->training->start;
			$end = $this->training->end;
			$city = $this->training->venueCity;
		} else {
			$this->dateId = $start = $end = $city = null;
			$this->training = $this->trainings->getIncludingCustom($this->application->trainingAction);
		}

		$this->template->pageTitle     = $this->application->name ?? 'smazáno';
		$this->template->applicationId = $this->applicationId;
		$this->template->dateId        = $this->dateId;
		$this->template->status        = $this->application->status;
		$this->template->statusTime    = $this->application->statusTime;
		$this->template->trainingName  = $this->training->name;
		$this->template->trainingStart = $start;
		$this->template->trainingEnd   = $end;
		$this->template->trainingCity  = $city;
		$this->template->sourceName    = $this->application->sourceName;
		$this->template->companyId     = $this->application->companyId;
		$this->template->attended      = in_array($this->application->status, $this->trainingStatuses->getAttendedStatuses());
		$this->template->toBeInvited   = in_array($this->application->status, $this->trainingStatuses->getParentStatuses(Statuses::STATUS_INVITED));
		$this->template->accessToken   = $this->application->accessToken;
		$this->template->history       = $this->trainingStatuses->getStatusHistory($this->applicationId);
	}


	public function actionPreliminary(): void
	{
		$this->template->pageTitle = 'Předběžné přihlášky';
		$this->template->preliminaryApplications = $this->trainingApplications->getPreliminary();
		$this->template->upcoming = $this->trainingDates->getPublicUpcoming();
	}


	public function renderDefault(): void
	{
		$trainings = $this->trainings->getAllTrainings();
		$this->addApplications($trainings);

		$this->template->pageTitle = 'Školení';
		$this->template->trainings = $trainings;
		$this->template->now = new DateTime();
		$this->template->upcomingIds = $this->trainingDates->getPublicUpcomingIds();
	}


	public function renderPastWithPersonalData(): void
	{
		$trainings = $this->trainings->getPastWithPersonalData();
		$this->addApplications($trainings);

		$this->template->pageTitle = 'Minulá školení s osobními daty starší než ' . $this->netxtenHelpers->localeDay($this->trainingDates->getDataRetentionDate());
		$this->template->trainings = $trainings;
	}


	private function addApplications(array &$trainings): void
	{
		foreach ($trainings as $training) {
			$training->applications = $this->trainingApplications->getValidByDate($training->dateId);
			$training->canceledApplications = $this->trainingApplications->getCanceledPaidByDate($training->dateId);
			$training->validCount = count($training->applications);
			$training->requiresAttention = false;
		}
	}


	protected function createComponentStatuses(string $formName): TrainingStatuses
	{
		$form = new TrainingStatuses($this, $formName, $this->applications, $this->translator);
		$form->getComponent('submit')->onClick[] = [$this, 'submittedStatuses'];
		$form->getComponent('familiar')->onClick[] = [$this, 'submittedFamiliar'];
		return $form;
	}


	public function submittedStatuses(SubmitButton $button): void
	{
		$values = $button->getForm()->getValues();
		foreach ($values->applications as $id => $status) {
			if ($status) {
				$this->trainingStatuses->updateStatus($id, $status, $values->date);
			}
		}
		$this->redirect($this->getAction(), $this->dateId);
	}


	public function submittedFamiliar(SubmitButton $button): void
	{
		$attendedStatuses = $this->trainingStatuses->getAttendedStatuses();
		$total = 0;
		foreach (array_keys((array)$button->getForm()->getValues()->applications) as $id) {
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


	protected function createComponentApplications(string $formName): TrainingApplicationMultiple
	{
		$statuses = array();
		foreach ($this->trainingStatuses->getInitialStatuses() as $status) {
			$statuses[$status] = $status;
		}

		$applications = $this->request->getPost('applications');
		$count = (is_array($applications) ? count($applications) : 1);
		$form = new TrainingApplicationMultiple($this, $formName, max($count, 1), $statuses, $this->trainingApplications, $this->translator);
		$form->onSuccess[] = [$this, 'submittedApplications'];
		return $form;
	}


	public function submittedApplications(Form $form, ArrayHash $values): void
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


	protected function createComponentEditReview(string $formName): TrainingReview
	{
		$form = new TrainingReview($this, $formName);
		$form->setReview($this->review);
		$form->onSuccess[] = [$this, 'submittedEditReview'];
		return $form;
	}


	public function submittedEditReview(Form $form, ArrayHash $values): void
	{
		$this->trainingReviews->updateReview(
			$this->review->reviewId,
			$this->review->dateId,
			$values->name,
			$values->company,
			$values->jobTitle ?: null,
			$values->review,
			$values->href ?: null,
			$values->hidden,
			$values->ranking ?: null
		);

		$this->redirect('date', $this->review->dateId);
	}


	protected function createComponentAddReview(string $formName): TrainingReview
	{
		$reviewApplicationNames = [];
		foreach ($this->trainingReviews->getReviewsByDateId($this->dateId) as $review) {
			if ($review->name !== null) {
				$reviewApplicationNames[] = $review->name;
			}
		}

		$applications = [];
		foreach ($this->trainingApplications->getByDate($this->dateId) as $application) {
			if (!$application->discarded) {
				$option = Html::el('option');
				if (in_array($application->name, $reviewApplicationNames)) {
					$option = $option->setDisabled(true);
				}
				$option->setText(($application->name ?? 'smazáno') . ($application->company ? ", {$application->company}" : ''));
				$option->addAttributes([
					'data-name' => $application->name ?? '',
					'data-company' => $application->company ?? '',
				]);
				$applications[$application->id] = $option;
			}
		}

		$form = new TrainingReview($this, $formName, $applications);
		$form->onSuccess[] = [$this, 'submittedAddReview'];
		return $form;
	}


	public function submittedAddReview(Form $form, $values): void
	{
		$this->trainingReviews->addReview(
			$this->dateId,
			$values->name,
			$values->company,
			$values->jobTitle ?: null,
			$values->review,
			$values->href ?: null,
			$values->hidden,
			$values->ranking ?: null
		);
		$this->redirect('date', $this->dateId);
	}


	protected function createComponentApplication(string $formName): TrainingApplicationAdmin
	{
		$form = new TrainingApplicationAdmin($this, $formName, $this->trainingApplications, $this->trainingDates, $this->translator);
		$form->setApplication($this->application);
		$form->onSuccess[] = [$this, 'submittedApplication'];
		return $form;
	}


	public function submittedApplication(Form $form, $values): void
	{
		$this->trainingApplications->updateApplicationData(
			$this->applicationId,
			$values->nameSet ? $values->name : null,
			$values->emailSet ? $values->email : null,
			$values->companySet ? $values->company : null,
			$values->streetSet ? $values->street : null,
			$values->citySet ? $values->city : null,
			$values->zipSet ? $values->zip : null,
			$values->countrySet ? $values->country : null,
			$values->companyIdSet ? $values->companyId : null,
			$values->companyTaxIdSet ? $values->companyTaxId : null,
			$values->noteSet ? $values->note : null,
			$values->source,
			(trim($values->price) !== '' ? (int)$values->price : null),
			(trim($values->vatRate) !== '' ? $values->vatRate / 100 : null),
			(is_float($values->priceVat) ? $values->priceVat : null),
			(trim($values->discount) !== '' ? (int)$values->discount : null),
			$values->invoiceId,
			$values->paid,
			$values->familiar,
			(isset($values->date) ? $values->date : null)
		);
		if (isset($this->dateId) || isset($values->date)) {
			$this->redirect('date', $values->date ?? $this->dateId);
		} else {
			$this->redirect('preliminary');
		}
	}


	protected function createComponentFile(string $formName): TrainingFile
	{
		$form = new TrainingFile($this, $formName);
		$form->onSuccess[] = [$this, 'submittedFile'];
		return $form;
	}


	public function submittedFile(Form $form, ArrayHash $values): void
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


	protected function createComponentDate(string $formName): TrainingDate
	{
		$form = new TrainingDate($this, $formName, $this->trainings, $this->trainingDates, $this->trainingVenues);
		$form->setTrainingDate($this->training);
		$form->onSuccess[] = [$this, 'submittedDate'];
		return $form;
	}


	public function submittedDate(Form $form, ArrayHash $values): void
	{
		$this->trainingDates->update(
			$this->dateId,
			$values->training,
			$values->venue,
			$values->start,
			$values->end,
			$values->label,
			$values->status,
			$values->public,
			$values->cooperation,
			$values->note
		);
		$this->flashMessage('Termín upraven');
		$this->redirect($this->getAction(), $this->redirectParam);
	}


	protected function createComponentAddDate(string $formName): TrainingDate
	{
		$form = new TrainingDate($this, $formName, $this->trainings, $this->trainingDates, $this->trainingVenues);
		$form->onSuccess[] = [$this, 'submittedAddDate'];
		return $form;
	}


	public function submittedAddDate(Form $form, ArrayHash $values): void
	{
		$this->trainingDates->add(
			$values->training,
			$values->venue,
			$values->start,
			$values->end,
			$values->label,
			$values->status,
			$values->public,
			$values->cooperation,
			$values->note
		);
		$this->redirect('Trainings:');
	}


	protected function createComponentDeletePersonalDataForm(): Form
	{
		return $this->deletePersonalDataFormFactory->create(function (): void {
			$this->flashMessage('Osobní data z minulých školení smazána');
			$this->redirect('Homepage:');
		});
	}

}
