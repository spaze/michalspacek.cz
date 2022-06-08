<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use DateTime;
use MichalSpacekCz\DateTime\DateTimeFormatter;
use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Form\DeletePersonalDataFormFactory;
use MichalSpacekCz\Form\TrainingApplicationAdminFactory;
use MichalSpacekCz\Form\TrainingApplicationMultiple;
use MichalSpacekCz\Form\TrainingDateFormFactory;
use MichalSpacekCz\Form\TrainingFileFormFactory;
use MichalSpacekCz\Form\TrainingReview;
use MichalSpacekCz\Form\TrainingStatuses;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Dates;
use MichalSpacekCz\Training\Files\TrainingFiles;
use MichalSpacekCz\Training\Reviews;
use MichalSpacekCz\Training\Statuses;
use MichalSpacekCz\Training\Trainings;
use Nette\Application\BadRequestException;
use Nette\Database\Row;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;

class TrainingsPresenter extends BasePresenter
{

	/** @var Row[] */
	private array $applications;

	/** @var int[] */
	private array $applicationIdsAllowedFiles = [];

	/** @var Row<mixed> */
	private Row $application;

	private int $applicationId;

	/** @var Row<mixed> */
	private Row $review;

	/** @var Row<mixed> */
	private Row $training;

	private ?int $dateId;

	private ?int $redirectParam;


	public function __construct(
		private readonly Applications $trainingApplications,
		private readonly Dates $trainingDates,
		private readonly Statuses $trainingStatuses,
		private readonly Trainings $trainings,
		private readonly TrainingFiles $trainingFiles,
		private readonly Reviews $trainingReviews,
		private readonly TrainingControlsFactory $trainingControlsFactory,
		private readonly DateTimeFormatter $dateTimeFormatter,
		private readonly DeletePersonalDataFormFactory $deletePersonalDataFormFactory,
		private readonly TrainingApplicationAdminFactory $trainingApplicationAdminFactory,
		private readonly TrainingFileFormFactory $trainingFileFormFactory,
		private readonly TrainingDateFormFactory $trainingDateFormFactory,
	) {
		parent::__construct();
	}


	/**
	 * @throws BadRequestException
	 */
	public function actionDate(int $param): void
	{
		$this->dateId = $param;
		$this->redirectParam = $this->dateId;
		$training = $this->trainingDates->get($this->dateId);
		if (!$training) {
			throw new BadRequestException("Date id {$param} does not exist, yet");
		}
		$this->training = $training;
		$validCount = 0;
		$applications = $discarded = [];
		foreach ($this->trainingApplications->getByDate($this->dateId) as $application) {
			if (!$application->discarded) {
				$validCount++;
				$applications[] = $application;
			} else {
				$discarded[] = $application;
			}
			if ($application->allowFiles) {
				$this->applicationIdsAllowedFiles[] = $application->id;
			}
			$application->childrenStatuses = $this->trainingStatuses->getChildrenStatusesForApplicationId($application->status, $application->id);
		}
		$this->applications = array_merge($applications, $discarded);

		$this->template->pageTitle     = 'Účastníci';
		$this->template->trainingStart = $this->training->start;
		$this->template->trainingEnd   = $this->training->end;
		$this->template->trainingName  = $this->training->name;
		$this->template->remote = $this->training->remote;
		$this->template->venueCity     = $this->training->venueCity;
		$this->template->venueName     = $this->training->venueName;
		$this->template->public        = $this->training->public;
		$this->template->note          = $this->training->note;
		$this->template->applications  = $this->applications;
		$this->template->validCount    = $validCount;
		$this->template->attendedStatuses = $this->trainingStatuses->getAttendedStatuses();
		$this->template->filesStatuses = $this->trainingStatuses->getAllowFilesStatuses();
		$this->template->reviews = $this->trainingReviews->getReviewsByDateId($this->dateId);
	}


	public function actionFiles(int $param): void
	{
		$this->applicationId = $param;
		$this->redirectParam = $this->applicationId;
		$application = $this->trainingApplications->getApplicationById($this->applicationId);
		if (!in_array($application->status, $this->trainingStatuses->getAllowFilesStatuses(), true)) {
			$this->redirect('date', $application->dateId);
		}

		$this->applicationIdsAllowedFiles = array($application->applicationId);
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

		$this->template->pageTitle = "Ohlas od {$this->review->name}" . ($this->review->company ? ", {$this->review->company}" : '');
		$this->template->trainingStart      = $date->start;
		$this->template->trainingEnd = $date->end;
		$this->template->trainingName       = $date->name;
		$this->template->trainingCity  = $date->venueCity;
		$this->template->name          = $this->review->name;
		$this->template->dateId        = $this->review->dateId;
	}


	/**
	 * @throws BadRequestException
	 */
	public function actionApplication(int $param): void
	{
		$this->applicationId = $param;
		$application = $this->trainingApplications->getApplicationById($this->applicationId);
		if (!$application) {
			throw new BadRequestException("No application with id {$this->applicationId}");
		}
		$this->application = $application;

		if (isset($this->application->dateId)) {
			$this->dateId = $this->application->dateId;
			$this->training = $this->trainingDates->get($this->dateId);
			$start = $this->training->start;
			$end = $this->training->end;
			$city = $this->training->venueCity;
			$isRemote = $this->training->remote;
		} else {
			$this->dateId = $start = $end = $city = $isRemote = null;
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
		$this->template->trainingRemote = $isRemote;
		$this->template->trainingCity  = $city;
		$this->template->sourceName    = $this->application->sourceName;
		$this->template->companyId     = $this->application->companyId;
		$this->template->allowFiles      = in_array($this->application->status, $this->trainingStatuses->getAllowFilesStatuses());
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

		$this->template->pageTitle = 'Minulá školení s osobními daty starší než ' . $this->dateTimeFormatter->localeDay($this->trainingDates->getDataRetentionDate());
		$this->template->trainings = $trainings;
	}


	/**
	 * @param Row[] $trainings
	 */
	private function addApplications(array $trainings): void
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
		$form = new TrainingStatuses($this, $formName, $this->applications, $this->trainingControlsFactory);
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
		foreach (array_keys((array)$button->getForm()->getUnsafeValues(null)->applications) as $id) {
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
				->addHtml(implode(', ', $statuses)),
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
		$form = new TrainingApplicationMultiple($this, $formName, max($count, 1), $statuses, $this->trainingControlsFactory);
		$form->onSuccess[] = [$this, 'submittedApplications'];
		return $form;
	}


	/**
	 * @param Form $form
	 * @param ArrayHash<int|string> $values
	 */
	public function submittedApplications(Form $form, ArrayHash $values): void
	{
		foreach ($values->applications as $application) {
			$this->trainingApplications->insertApplication(
				$this->training->trainingId,
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
				$this->training->price,
				$this->training->studentDiscount,
				$values->status,
				$values->source,
				$values->date,
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


	/**
	 * @param Form $form
	 * @param ArrayHash<int|string> $values
	 */
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
			$values->ranking ?: null,
			$values->note ?: null,
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


	/**
	 * @param Form $form
	 * @param ArrayHash<int|string> $values
	 */
	public function submittedAddReview(Form $form, ArrayHash $values): void
	{
		$this->trainingReviews->addReview(
			$this->dateId,
			$values->name,
			$values->company,
			$values->jobTitle ?: null,
			$values->review,
			$values->href ?: null,
			$values->hidden,
			$values->ranking ?: null,
			$values->note ?: null,
		);
		$this->redirect('date', $this->dateId);
	}


	protected function createComponentApplicationForm(): Form
	{
		return $this->trainingApplicationAdminFactory->create(
			function (?int $dateId): void {
				if (isset($this->dateId) || isset($dateId)) {
					$this->redirect('date', $dateId ?? $this->dateId);
				} else {
					$this->redirect('preliminary');
				}
			},
			function (): void {
				$this->redirect('this');
			},
			$this->application,
		);
	}


	protected function createComponentFile(): Form
	{
		return $this->trainingFileFormFactory->create(
			function (?string $uploadedFilename): void {
				if ($uploadedFilename !== null) {
					$this->flashMessage(
						Html::el()->setText('Soubor ')
							->addHtml(Html::el('code')->setText($uploadedFilename))
							->addHtml(Html::el()->setText(' byl přidán')),
					);
				} else {
					$this->flashMessage('Soubor nebyl vybrán nebo došlo k nějaké chybě při nahrávání', 'error');
				}
				$this->redirect($this->getAction(), $this->redirectParam);
			},
			$this->training->start,
			$this->applicationIdsAllowedFiles,
		);
	}


	protected function createComponentEditDate(): Form
	{
		return $this->trainingDateFormFactory->create(
			function (): void {
				$this->flashMessage('Termín upraven');
				$this->redirect($this->getAction(), $this->redirectParam);
			},
			$this->training,
		);
	}


	protected function createComponentAddDate(): Form
	{
		return $this->trainingDateFormFactory->create(
			function (): void {
				$this->redirect('Trainings:');
			},
		);
	}


	protected function createComponentDeletePersonalDataForm(): Form
	{
		return $this->deletePersonalDataFormFactory->create(function (): void {
			$this->flashMessage('Osobní data z minulých školení smazána');
			$this->redirect('Homepage:');
		});
	}

}
