<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\DateTime\DateTimeFormatter;
use MichalSpacekCz\Form\DeletePersonalDataFormFactory;
use MichalSpacekCz\Form\TrainingApplicationAdminFormFactory;
use MichalSpacekCz\Form\TrainingApplicationMultipleFormFactory;
use MichalSpacekCz\Form\TrainingFileFormFactory;
use MichalSpacekCz\Form\TrainingStatusesFormFactory;
use MichalSpacekCz\Training\Applications\TrainingApplications;
use MichalSpacekCz\Training\DateList\DateListOrder;
use MichalSpacekCz\Training\DateList\TrainingApplicationsList;
use MichalSpacekCz\Training\DateList\TrainingApplicationsListFactory;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\TrainingDateInputs;
use MichalSpacekCz\Training\Dates\TrainingDateInputsFactory;
use MichalSpacekCz\Training\Dates\TrainingDates;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use MichalSpacekCz\Training\Exceptions\TrainingApplicationDoesNotExistException;
use MichalSpacekCz\Training\Exceptions\TrainingDateDoesNotExistException;
use MichalSpacekCz\Training\Files\TrainingFiles;
use MichalSpacekCz\Training\Reviews\TrainingReview;
use MichalSpacekCz\Training\Reviews\TrainingReviewInputs;
use MichalSpacekCz\Training\Reviews\TrainingReviewInputsFactory;
use MichalSpacekCz\Training\Reviews\TrainingReviews;
use MichalSpacekCz\Training\Statuses;
use MichalSpacekCz\Training\Trainings\Trainings;
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

	private TrainingReview $review;

	private TrainingDate $training;

	private int $dateId;

	private int $redirectParam;

	/** @var list<TrainingDate> */
	private array $pastWithPersonalData = [];


	public function __construct(
		private readonly TrainingApplications $trainingApplications,
		private readonly TrainingDates $trainingDates,
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly Statuses $trainingStatuses,
		private readonly Trainings $trainings,
		private readonly TrainingFiles $trainingFiles,
		private readonly TrainingReviews $trainingReviews,
		private readonly DateTimeFormatter $dateTimeFormatter,
		private readonly DeletePersonalDataFormFactory $deletePersonalDataFormFactory,
		private readonly TrainingApplicationAdminFormFactory $trainingApplicationAdminFactory,
		private readonly TrainingApplicationMultipleFormFactory $trainingApplicationMultipleFormFactory,
		private readonly TrainingFileFormFactory $trainingFileFormFactory,
		private readonly TrainingDateInputsFactory $trainingDateInputsFactory,
		private readonly TrainingStatusesFormFactory $trainingStatusesFormFactory,
		private readonly TrainingApplicationsListFactory $trainingApplicationsListFactory,
		private readonly TrainingReviewInputsFactory $trainingReviewInputsFactory,
	) {
		parent::__construct();
	}


	public function actionDate(int $param): void
	{
		$this->dateId = $param;
		$this->redirectParam = $this->dateId;
		try {
			$this->training = $this->trainingDates->get($this->dateId);
		} catch (TrainingDateDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
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
			if ($application->allowFiles) {
				$this->applicationIdsAllowedFiles[] = $application->id;
			}
			$application->childrenStatuses = $this->trainingStatuses->getChildrenStatusesForApplicationId($application->status, $application->id);
		}
		$this->applications = array_merge($applications, $discarded);

		$this->template->pageTitle = 'Účastníci';
		$this->template->trainingStart = $this->training->getStart();
		$this->template->trainingEnd = $this->training->getEnd();
		$this->template->trainingName = $this->training->getName();
		$this->template->remote = $this->training->isRemote();
		$this->template->venueCity = $this->training->getVenueCity();
		$this->template->venueName = $this->training->getVenueName();
		$this->template->public = $this->training->isPublic();
		$this->template->note = $this->training->getNote();
		$this->template->applications = $this->applications;
		$this->template->validCount = $validCount;
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

		$this->applicationIdsAllowedFiles = [$application->applicationId];
		$this->training = $this->trainingDates->get($application->dateId);

		$this->template->pageTitle = 'Soubory';
		$this->template->files = $this->trainingFiles->getFiles($this->applicationId);
		$this->template->trainingStart = $this->training->getStart();
		$this->template->trainingEnd = $this->training->getEnd();
		$this->template->trainingName = $this->training->getName();
		$this->template->trainingCity = $this->training->getVenueCity();
		$this->template->name = $application->name;
		$this->template->dateId = $application->dateId;
	}


	public function actionReview(int $param): void
	{
		$this->review = $this->trainingReviews->getReview($param);
		$date = $this->trainingDates->get($this->review->getDateId());

		$this->template->pageTitle = "Ohlas od {$this->review->getName()}" . ($this->review->getCompany() ? ", {$this->review->getCompany()}" : '');
		$this->template->trainingStart = $date->getStart();
		$this->template->trainingEnd = $date->getEnd();
		$this->template->trainingName = $date->getName();
		$this->template->trainingCity = $date->getVenueCity();
		$this->template->name = $this->review->getName();
		$this->template->dateId = $this->review->getDateId();
	}


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
			$training = $this->trainingDates->get($applicationDateId);
			$name = $training->getName();
			$start = $training->getStart();
			$end = $training->getEnd();
			$city = $training->getVenueCity();
			$isRemote = $training->isRemote();
		} else {
			$applicationDateId = $start = $end = $city = $isRemote = null;
			$name = $this->trainings->getIncludingCustom($this->application->trainingAction)->getName();
		}

		$this->template->pageTitle = $this->application->name ?? 'smazáno';
		$this->template->applicationId = $this->applicationId;
		$this->template->applicationDateId = $applicationDateId;
		$this->template->status = $this->application->status;
		$this->template->statusTime = $this->application->statusTime;
		$this->template->trainingName = $name;
		$this->template->trainingStart = $start;
		$this->template->trainingEnd = $end;
		$this->template->trainingRemote = $isRemote;
		$this->template->trainingCity = $city;
		$this->template->sourceName = $this->application->sourceName;
		$this->template->companyId = $this->application->companyId;
		$this->template->allowFiles = in_array($this->application->status, $this->trainingStatuses->getAllowFilesStatuses());
		$this->template->toBeInvited = in_array($this->application->status, $this->trainingStatuses->getParentStatuses(Statuses::STATUS_INVITED));
		$this->template->accessToken = $this->application->accessToken;
		$this->template->history = $this->trainingStatuses->getStatusHistory($this->applicationId);
	}


	public function actionPreliminary(): void
	{
		$this->template->pageTitle = 'Předběžné přihlášky';
		$this->template->preliminaryApplications = $this->trainingApplications->getPreliminary();
		$this->template->upcoming = $this->upcomingTrainingDates->getPublicUpcoming();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = 'Školení';
	}


	public function actionPastWithPersonalData(): void
	{
		$this->pastWithPersonalData = $this->trainingDates->getPastWithPersonalData();
		$this->template->pageTitle = 'Minulá školení s osobními daty starší než ' . $this->dateTimeFormatter->localeDay($this->trainingDates->getDataRetentionDate());
		$this->template->trainings = (bool)$this->pastWithPersonalData;
	}


	/**
	 * @param list<TrainingDate> $trainings
	 */
	private function addApplications(array $trainings): void
	{
		foreach ($trainings as $training) {
			$training->setApplications($this->trainingApplications->getValidByDate($training->getId()));
			$training->setCanceledApplications($this->trainingApplications->getCanceledPaidByDate($training->getId()));
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
			$this->training->getTrainingId(),
			$this->dateId,
			$this->training->getPrice(),
			$this->training->getStudentDiscount(),
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
			$this->training->getStart(),
			$this->applicationIdsAllowedFiles,
		);
	}


	protected function createComponentEditTrainingDateInputs(): TrainingDateInputs
	{
		return $this->trainingDateInputsFactory->createFor($this->training, $this->redirectParam);
	}


	protected function createComponentAddTrainingDateInputs(): TrainingDateInputs
	{
		return $this->trainingDateInputsFactory->create();
	}


	protected function createComponentDeletePersonalDataForm(): Form
	{
		return $this->deletePersonalDataFormFactory->create(function (): never {
			$this->flashMessage('Osobní data z minulých školení smazána');
			$this->redirect('Homepage:');
		});
	}


	protected function createComponentTrainingApplicationsList(): TrainingApplicationsList
	{
		$dates = $this->trainingDates->getAllTrainings();
		$this->addApplications($dates);
		return $this->trainingApplicationsListFactory->create($dates, DateListOrder::Desc);
	}


	protected function createComponentPastWithPersonalDataTrainingApplicationsList(): TrainingApplicationsList
	{
		$this->addApplications($this->pastWithPersonalData);
		return $this->trainingApplicationsListFactory->create($this->pastWithPersonalData, DateListOrder::Desc, true);
	}


	protected function createComponentEditReviewInputs(): TrainingReviewInputs
	{
		return $this->trainingReviewInputsFactory->create(false, $this->review->getDateId(), $this->review);
	}


	protected function createComponentAddReviewInputs(): TrainingReviewInputs
	{
		return $this->trainingReviewInputsFactory->create(true, $this->dateId);
	}

}
