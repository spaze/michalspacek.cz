<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use DateTime;
use MichalSpacekCz\DateTime\DateTimeFormatter;
use MichalSpacekCz\Form\DeletePersonalDataFormFactory;
use MichalSpacekCz\Form\TrainingApplicationAdminFormFactory;
use MichalSpacekCz\Form\TrainingApplicationMultipleFormFactory;
use MichalSpacekCz\Form\TrainingDateFormFactory;
use MichalSpacekCz\Form\TrainingFileFormFactory;
use MichalSpacekCz\Form\TrainingReviewFormFactory;
use MichalSpacekCz\Form\TrainingStatusesFormFactory;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Dates;
use MichalSpacekCz\Training\Exceptions\TrainingApplicationDoesNotExistException;
use MichalSpacekCz\Training\Exceptions\TrainingDateDoesNotExistException;
use MichalSpacekCz\Training\Files\TrainingFiles;
use MichalSpacekCz\Training\Reviews;
use MichalSpacekCz\Training\Statuses;
use MichalSpacekCz\Training\Trainings;
use Nette\Application\BadRequestException;
use Nette\Database\Row;
use Nette\Forms\Form;
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

	private int $dateId;

	private int $redirectParam;


	public function __construct(
		private readonly Applications $trainingApplications,
		private readonly Dates $trainingDates,
		private readonly Statuses $trainingStatuses,
		private readonly Trainings $trainings,
		private readonly TrainingFiles $trainingFiles,
		private readonly Reviews $trainingReviews,
		private readonly DateTimeFormatter $dateTimeFormatter,
		private readonly DeletePersonalDataFormFactory $deletePersonalDataFormFactory,
		private readonly TrainingApplicationAdminFormFactory $trainingApplicationAdminFactory,
		private readonly TrainingApplicationMultipleFormFactory $trainingApplicationMultipleFormFactory,
		private readonly TrainingFileFormFactory $trainingFileFormFactory,
		private readonly TrainingDateFormFactory $trainingDateFormFactory,
		private readonly TrainingReviewFormFactory $trainingReviewFormFactory,
		private readonly TrainingStatusesFormFactory $trainingStatusesFormFactory,
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
		try {
			$training = $this->trainingDates->get($this->dateId);
		} catch (TrainingDateDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
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
		try {
			$application = $this->trainingApplications->getApplicationById($this->applicationId);
		} catch (TrainingApplicationDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}
		$this->application = $application;

		if (isset($this->application->dateId)) {
			$applicationDateId = $this->application->dateId;
			$this->training = $this->trainingDates->get($applicationDateId);
			$start = $this->training->start;
			$end = $this->training->end;
			$city = $this->training->venueCity;
			$isRemote = $this->training->remote;
		} else {
			$applicationDateId = $start = $end = $city = $isRemote = null;
			$this->training = $this->trainings->getIncludingCustom($this->application->trainingAction);
		}

		$this->template->pageTitle     = $this->application->name ?? 'smazáno';
		$this->template->applicationId = $this->applicationId;
		$this->template->applicationDateId = $applicationDateId;
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


	protected function createComponentStatuses(): Form
	{
		return $this->trainingStatusesFormFactory->create(
			function (?Html $message): never {
				if ($message) {
					$this->flashMessage($message);
				}
				$this->redirect($this->getAction(), $this->dateId);
			},
			$this->applications,
		);
	}


	protected function createComponentApplications(): Form
	{
		return $this->trainingApplicationMultipleFormFactory->create(
			function (int $dateId): never {
				$this->redirect($this->getAction(), $dateId);
			},
			$this->request,
			$this->training->trainingId,
			$this->dateId,
			$this->training->price,
			$this->training->studentDiscount,
		);
	}


	protected function createComponentEditReview(): Form
	{
		return $this->trainingReviewFormFactory->create(
			function (int $dateId): never {
				$this->redirect('date', $dateId);
			},
			$this->review->dateId,
			$this->review,
		);
	}


	protected function createComponentAddReview(): Form
	{
		return $this->trainingReviewFormFactory->create(
			function (int $dateId): never {
				$this->redirect('date', $dateId);
			},
			$this->dateId,
		);
	}


	protected function createComponentApplicationForm(): Form
	{
		return $this->trainingApplicationAdminFactory->create(
			function (?int $dateId): never {
				if (isset($this->dateId) || isset($dateId)) {
					$this->redirect('date', $dateId ?? $this->dateId);
				} else {
					$this->redirect('preliminary');
				}
			},
			function (): never {
				$this->redirect('this');
			},
			$this->application,
		);
	}


	protected function createComponentFile(): Form
	{
		return $this->trainingFileFormFactory->create(
			function (Html|string $message, string $type): never {
				$this->flashMessage($message, $type);
				$this->redirect($this->getAction(), $this->redirectParam);
			},
			$this->training->start,
			$this->applicationIdsAllowedFiles,
		);
	}


	protected function createComponentEditDate(): Form
	{
		return $this->trainingDateFormFactory->create(
			function (): never {
				$this->flashMessage('Termín upraven');
				$this->redirect($this->getAction(), $this->redirectParam);
			},
			$this->training,
		);
	}


	protected function createComponentAddDate(): Form
	{
		return $this->trainingDateFormFactory->create(
			function (): never {
				$this->redirect('Trainings:');
			},
		);
	}


	protected function createComponentDeletePersonalDataForm(): Form
	{
		return $this->deletePersonalDataFormFactory->create(function (): never {
			$this->flashMessage('Osobní data z minulých školení smazána');
			$this->redirect('Homepage:');
		});
	}

}
