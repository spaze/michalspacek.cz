<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use Contributte\Translation\Translator;
use MichalSpacekCz\CompanyInfo\CompanyInfo;
use MichalSpacekCz\Form\TrainingApplicationFormFactory;
use MichalSpacekCz\Form\TrainingApplicationPreliminaryFormFactory;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Applications\TrainingApplications;
use MichalSpacekCz\Training\Applications\TrainingApplicationSessionSection;
use MichalSpacekCz\Training\Company\CompanyTrainings;
use MichalSpacekCz\Training\DateList\UpcomingTrainingDatesList;
use MichalSpacekCz\Training\DateList\UpcomingTrainingDatesListFactory;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\TrainingDates;
use MichalSpacekCz\Training\Discontinued\DiscontinuedTrainings;
use MichalSpacekCz\Training\Exceptions\TrainingDoesNotExistException;
use MichalSpacekCz\Training\Files\TrainingFilesDownload;
use MichalSpacekCz\Training\FreeSeats;
use MichalSpacekCz\Training\Reviews\TrainingReviews;
use MichalSpacekCz\Training\TrainingLocales;
use MichalSpacekCz\Training\Trainings\Training;
use MichalSpacekCz\Training\Trainings\Trainings;
use Nette\Application\BadRequestException;
use Nette\Forms\Form;
use Nette\Http\IResponse;
use ParagonIE\Halite\Alerts\HaliteAlert;
use SodiumException;

class TrainingsPresenter extends BasePresenter
{

	private ?Training $training = null;

	/** @var array<int, TrainingDate> id => date */
	private array $dates = [];

	private ?string $trainingAction = null;


	public function __construct(
		private readonly TexyFormatter $texyFormatter,
		private readonly TrainingApplications $trainingApplications,
		private readonly TrainingDates $trainingDates,
		private readonly Trainings $trainings,
		private readonly FreeSeats $freeSeats,
		private readonly CompanyTrainings $companyTrainings,
		private readonly DiscontinuedTrainings $discontinuedTrainings,
		private readonly TrainingLocales $trainingLocales,
		private readonly TrainingReviews $trainingReviews,
		private readonly TrainingApplicationFormFactory $trainingApplicationFactory,
		private readonly TrainingApplicationPreliminaryFormFactory $trainingApplicationPreliminaryFactory,
		private readonly UpcomingTrainingDatesListFactory $upcomingTrainingDatesListFactory,
		private readonly CompanyInfo $companyInfo,
		private readonly TrainingFilesDownload $trainingFilesDownload,
		private readonly Translator $translator,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.trainings');
		$this->template->companyTrainings = $this->companyTrainings->getWithoutPublicUpcoming();
		$this->template->discontinued = $this->discontinuedTrainings->getAllDiscontinued();
	}


	protected function createComponentUpcomingDatesList(): UpcomingTrainingDatesList
	{
		return $this->upcomingTrainingDatesListFactory->create();
	}


	public function actionTraining(string $name): void
	{
		$this->trainingAction = $name;
		try {
			$training = $this->trainings->get($name);
		} catch (TrainingDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}
		$this->training = $training;

		$this->redirectToSuccessor($training->getSuccessorId());

		$this->dates = $this->trainingDates->getDates($training->getId());

		$session = $this->getSession();
		$session->start(); // in createComponentApplication() it's too late as the session cookie cannot be set because the output is already sent

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.training', [$training->getName()->render()]);
		$this->template->training = $training;
		$this->template->lastFreeSeats = $this->freeSeats->lastFreeSeatsAnyDate($this->dates);
		$this->template->dates = $this->dates;
		$this->template->singleDate = count($this->dates) === 1 ? $this->trainingDates->formatDateVenueForUser(reset($this->dates)) : null;
		$this->template->dataRetention = $this->trainingDates->getDataRetentionDays();
		$this->template->reviews = $this->trainingReviews->getVisibleReviews($training->getId(), 3);
		$this->template->loadCompanyDataVisible = $this->companyInfo->isLoadCompanyDataVisible();
		$this->discontinuedTrainings->maybeMarkAsDiscontinued($this->template, $training->getDiscontinuedId());
	}


	public function actionApplication(string $name, ?string $param): void
	{
		$this->trainingAction = $name;
		try {
			$training = $this->trainings->get($name);
		} catch (TrainingDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}
		if ($training->getDiscontinuedId()) {
			throw new BadRequestException("I don't do {$name} training anymore");
		}

		$session = $this->getTrainingSessionSection();

		$application = $param ? $this->trainingApplications->getApplicationByToken($param) : null;
		if (!$application) {
			$session->removeApplicationValues();
			$this->redirect('training', $name);
		}
		$session->setApplicationForTraining($name, $application);
		$this->redirect('training', $application->getTrainingAction());
	}


	protected function createComponentApplication(): Form
	{
		if (!$this->training) {
			throw new ShouldNotHappenException('actionTraining() or actionSuccess() will be called first');
		}
		return $this->trainingApplicationFactory->create(
			function (string $name): never {
				$this->redirect('success', $name);
			},
			function (string $message): void {
				$this->flashMessage($this->translator->translate($message), 'error');
			},
			$this->training->getAction(),
			$this->training->getName(),
			$this->dates,
			$this->getTrainingSessionSection(),
		);
	}


	protected function createComponentApplicationPreliminary(): Form
	{
		if (!$this->training) {
			throw new ShouldNotHappenException('actionTraining() will be called first');
		}
		if ($this->training->getDiscontinuedId()) {
			throw new BadRequestException("No signups for discontinued trainings id {$this->training->getDiscontinuedId()}");
		}
		return $this->trainingApplicationPreliminaryFactory->create(
			function (string $action): never {
				$this->flashMessage($this->translator->translate('messages.trainings.submitted.preliminary'));
				$this->redirect('training#' . $this->translator->translate('html.id.application'), $action);
			},
			function (string $message): void {
				$this->flashMessage($this->translator->translate($message), 'error');
			},
			$this->training->getId(),
			$this->training->getAction(),
		);
	}


	public function actionReviews(string $name): void
	{
		$this->trainingAction = $name;
		try {
			$training = $this->trainings->get($name);
		} catch (TrainingDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}

		$this->redirectToSuccessor($training->getSuccessorId());

		$this->template->name = $training->getAction();
		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.trainingreviews', [$training->getName()->render()]);
		$this->template->title = $training->getName();
		$this->template->description = $training->getDescription();
		$this->template->reviews = $this->trainingReviews->getVisibleReviews($training->getId());
		$this->discontinuedTrainings->maybeMarkAsDiscontinued($this->template, $training->getDiscontinuedId());
	}


	/**
	 * @throws HaliteAlert
	 * @throws SodiumException
	 */
	public function actionFiles(string $name, ?string $param): void
	{
		$this->trainingAction = $name;
		try {
			$training = $this->trainings->getIncludingCustom($name);
		} catch (TrainingDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}
		$application = $this->trainingFilesDownload->start($name, $param);
		$trainingStart = $application->getTrainingStart();
		$trainingEnd = $application->getTrainingEnd();
		if (!$trainingStart || !$trainingEnd) {
			throw new ShouldNotHappenException(sprintf("Training application id '%s' should have both training start and end set", $application->getId()));
		}

		if ($application->getTrainingAction() !== $name) {
			$this->redirect('files', $application->getTrainingAction());
		}

		$this->trainingApplications->setAccessTokenUsed($application);
		if (count($application->getFiles()) === 0) {
			throw new BadRequestException('No files for application id ' . $application->getId());
		}

		$this->template->trainingTitle = $training->getName();
		$this->template->trainingName = ($training->isCustom() ? null : $training->getAction());
		$this->template->trainingStart = $trainingStart;
		$this->template->trainingEnd = $trainingEnd;
		$this->template->familiar = $application->isFamiliar();
		$remote = $application->isRemote() && !$application->isAttended();
		$this->template->remote = $remote;

		$this->template->pageTitle = $this->texyFormatter->translate(($remote ? 'messages.title.trainingmaterials.remote' : 'messages.title.trainingmaterials.regular'), [$training->getName()->render()]);
		$this->template->files = $application->getFiles();
	}


	public function actionSuccess(string $name): void
	{
		$this->trainingAction = $name;
		try {
			$training = $this->trainings->get($name);
		} catch (TrainingDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}
		if ($training->getDiscontinuedId()) {
			throw new BadRequestException("I don't do {$name} training anymore");
		}

		$this->training = $training;
		$this->dates = $this->trainingDates->getDates($this->training->getId());
		if (empty($this->dates)) {
			throw new BadRequestException("No dates for {$name} training", IResponse::S503_ServiceUnavailable);
		}

		$session = $this->getTrainingSessionSection();
		$dateId = $session->getDateId();
		if (!$dateId) {
			$this->redirect('training', $name);
		}

		if (!isset($this->dates[$dateId])) {
			$date = $this->trainingDates->get($dateId);
			$this->redirect('success', $date->getAction());
		}

		$date = $this->dates[$dateId];
		if ($date->isTentative()) {
			$this->flashMessage($this->translator->translate('messages.trainings.submitted.tentative'));
		} else {
			$this->flashMessage($this->translator->translate('messages.trainings.submitted.confirmed'));
		}

		$this->template->name = $this->training->getAction();
		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.trainingapplication', [$this->training->getName()->render()]);
		$this->template->title = $this->training->getName();
		$this->template->description = $this->training->getDescription();
		$this->template->start = $date->getStart();
		$this->template->end = $date->getEnd();
		$this->template->remote = $date->isRemote();
		$this->template->venueCity = $date->getVenueCity();
		$this->template->tentative = $date->isTentative();
		$this->template->form = $this->createComponentApplication();
		$this->template->reviews = $this->trainingReviews->getVisibleReviews($this->training->getId(), 3);
	}


	protected function createComponentOtherUpcomingDatesList(): UpcomingTrainingDatesList
	{
		return $this->upcomingTrainingDatesListFactory->createExclude($this->trainingAction);
	}


	/**
	 * Translated locale parameters for trainings.
	 *
	 * @return array<string, array<string, string|null>>
	 */
	protected function getLocaleLinkParams(): array
	{
		return $this->trainingLocales->getLocaleLinkParams($this->trainingAction, $this->getParameters());
	}


	private function redirectToSuccessor(?int $successorId): void
	{
		if ($successorId !== null) {
			$this->redirectPermanent('this', $this->trainings->getActionById($successorId));
		}
	}


	private function getTrainingSessionSection(): TrainingApplicationSessionSection
	{
		$session = $this->getSession()->getSection('training', TrainingApplicationSessionSection::class);
		if (!$session instanceof TrainingApplicationSessionSection) {
			throw new ShouldNotHappenException(sprintf('Session section type is %s, but should be %s', get_debug_type($session), TrainingApplicationSessionSection::class));
		}
		return $session;
	}

}
