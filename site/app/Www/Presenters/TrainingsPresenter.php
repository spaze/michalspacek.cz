<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\CompanyInfo\Info;
use MichalSpacekCz\Form\TrainingApplicationFormFactory;
use MichalSpacekCz\Form\TrainingApplicationPreliminaryFormFactory;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\CompanyTrainings;
use MichalSpacekCz\Training\Dates;
use MichalSpacekCz\Training\Exceptions\TrainingApplicationDoesNotExistException;
use MichalSpacekCz\Training\Exceptions\TrainingDoesNotExistException;
use MichalSpacekCz\Training\Files\TrainingFiles;
use MichalSpacekCz\Training\Locales;
use MichalSpacekCz\Training\Reviews;
use MichalSpacekCz\Training\Trainings;
use Nette\Application\BadRequestException;
use Nette\Database\Row;
use Nette\Forms\Form;
use Nette\Http\IResponse;

class TrainingsPresenter extends BasePresenter
{

	/** @var Row<mixed> */
	private Row $training;

	/** @var Row[] */
	private array $dates;


	public function __construct(
		private readonly TexyFormatter $texyFormatter,
		private readonly Applications $trainingApplications,
		private readonly Dates $trainingDates,
		private readonly TrainingFiles $trainingFiles,
		private readonly Trainings $trainings,
		private readonly CompanyTrainings $companyTrainings,
		private readonly Locales $trainingLocales,
		private readonly Reviews $trainingReviews,
		private readonly TrainingApplicationFormFactory $trainingApplicationFactory,
		private readonly TrainingApplicationPreliminaryFormFactory $trainingApplicationPreliminaryFactory,
		private readonly Info $companyInfo,
		private readonly IResponse $httpResponse,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.trainings');
		$this->template->upcomingTrainings = $this->trainingDates->getPublicUpcoming();
		$this->template->companyTrainings = $this->companyTrainings->getWithoutPublicUpcoming();
		$this->template->lastFreeSeats = $this->trainings->lastFreeSeatsAnyTraining($this->template->upcomingTrainings);
		$this->template->discontinued = $this->trainings->getAllDiscontinued();
	}


	/**
	 * @param string $name
	 */
	public function actionTraining(string $name): void
	{
		try {
			$training = $this->trainings->get($name);
		} catch (TrainingDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}
		$this->training = $training;

		$this->redirectToSuccessor($this->training->successorId);

		$this->dates = $this->trainings->getDates($this->training->trainingId);

		$session = $this->getSession();
		$session->start(); // in createComponentApplication() it's too late as the session cookie cannot be set because the output is already sent

		$this->template->name = $this->training->action;
		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.training', [$this->training->name]);
		$this->template->title = $this->training->name;
		$this->template->description = $this->training->description;
		$this->template->content = $this->training->content;
		$this->template->upsell = $this->training->upsell;
		$this->template->prerequisites = $this->training->prerequisites;
		$this->template->audience = $this->training->audience;
		$this->template->capacity = $this->training->capacity;
		$this->template->materials = $this->training->materials;
		$this->template->lastFreeSeats = $this->trainingDates->lastFreeSeatsAnyDate($this->dates);
		$this->template->dates = $this->dates;
		$this->template->singleDate = count($this->dates) === 1 ? $this->trainingDates->formatDateVenueForUser(reset($this->dates)) : null;
		$this->template->dataRetention = $this->trainingDates->getDataRetentionDays();

		$this->template->reviews = $this->trainingReviews->getVisibleReviews($this->training->trainingId, 3);

		$this->template->loadCompanyDataVisible = $this->companyInfo->isLoadCompanyDataVisible();

		if ($this->training->discontinuedId !== null) {
			$this->template->discontinued = [$this->trainings->getDiscontinued($this->training->discontinuedId)];
			$this->httpResponse->setCode(IResponse::S410_Gone);
		}
	}


	public function actionApplication(string $name, ?string $param): void
	{
		try {
			$training = $this->trainings->get($name);
		} catch (TrainingDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}
		if ($training->discontinuedId) {
			throw new BadRequestException("I don't do {$name} training anymore");
		}

		$session = $this->getSession('training');

		$application = $param ? $this->trainingApplications->getApplicationByToken($param) : null;
		if (!$application) {
			unset(
				$session->application,
				$session->name,
				$session->email,
				$session->company,
				$session->street,
				$session->city,
				$session->zip,
				$session->country,
				$session->companyId,
				$session->companyTaxId,
				$session->note,
			);
			$this->redirect('training', $name);
		}

		$data = (array)$session->application;
		$data[$name] = ['id' => $application->applicationId, 'dateId' => $application->dateId];
		$session->application = $data;

		$session->name = $application->name;
		$session->email = $application->email;
		$session->company = $application->company;
		$session->street = $application->street;
		$session->city = $application->city;
		$session->zip = $application->zip;
		$session->country = $application->country;
		$session->companyId = $application->companyId;
		$session->companyTaxId = $application->companyTaxId;
		$session->note = $application->note;

		$this->redirect('training', $application->trainingAction);
	}


	protected function createComponentApplication(): Form
	{
		return $this->trainingApplicationFactory->create(
			function (string $name): never {
				$this->redirect('success', $name);
			},
			function (string $message): void {
				$this->flashMessage($this->translator->translate($message), 'error');
			},
			$this->training->action,
			$this->training->name,
			$this->dates,
			$this->session->getSection('training'),
		);
	}


	protected function createComponentApplicationPreliminary(string $formName): Form
	{
		if ($this->training->discontinuedId) {
			throw new BadRequestException("No signups for discontinued trainings id {$this->training->discontinuedId}");
		}
		return $this->trainingApplicationPreliminaryFactory->create(
			function (string $action): never {
				$this->flashMessage($this->translator->translate('messages.trainings.submitted.preliminary'));
				$this->redirect('training#' . $this->translator->translate('html.id.application'), $action);
			},
			function (string $message): void {
				$this->flashMessage($this->translator->translate($message), 'error');
			},
			$this->training->trainingId,
			$this->training->action,
		);
	}


	public function actionReviews(string $name): void
	{
		try {
			$training = $this->trainings->get($name);
		} catch (TrainingDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}

		$this->redirectToSuccessor($training->successorId);

		$this->template->name = $training->action;
		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.trainingreviews', [$training->name]);
		$this->template->title = $training->name;
		$this->template->description = $training->description;
		$this->template->reviews = $this->trainingReviews->getVisibleReviews($training->trainingId);

		if ($training->discontinuedId !== null) {
			$this->template->discontinued = [$this->trainings->getDiscontinued($training->discontinuedId)];
			$this->httpResponse->setCode(IResponse::S410_Gone);
		}
	}


	public function actionFiles(string $name, ?string $param): void
	{
		$session = $this->getSession('application');

		if ($param !== null) {
			$application = $this->trainingApplications->getApplicationByToken($param);
			$session->token = $param;
			$session->applicationId = ($application ? $application->applicationId : null);
			$this->redirect('files', ($application ? $application->trainingAction : $name));
		}

		if (!$session->applicationId || !$session->token) {
			throw new BadRequestException("Unknown application id, missing or invalid token");
		}

		try {
			$training = $this->trainings->getIncludingCustom($name);
		} catch (TrainingDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}

		try {
			$application = $this->trainingApplications->getApplicationById($session->applicationId);
		} catch (TrainingApplicationDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}

		if ($application->trainingAction != $name) {
			$this->redirect('files', $application->trainingAction);
		}

		$files = $this->trainingFiles->getFiles($application->applicationId);
		$this->trainingApplications->setAccessTokenUsed($application);
		if (count($files) === 0) {
			throw new BadRequestException("No files for application id {$session->applicationId}");
		}

		$this->template->trainingTitle = $training->name;
		$this->template->trainingName = ($training->custom ? null : $training->action);
		$this->template->trainingStart = $application->trainingStart;
		$this->template->trainingEnd = $application->trainingEnd;
		$this->template->familiar = $application->familiar;
		$remote = $application->remote && !$application->attended;
		$this->template->remote = $remote;

		$this->template->pageTitle = $this->texyFormatter->translate(($remote ? 'messages.title.trainingmaterials.remote' : 'messages.title.trainingmaterials.regular'), [$training->name]);
		$this->template->files = $files;
	}


	public function actionSuccess(string $name): void
	{
		try {
			$training = $this->trainings->get($name);
		} catch (TrainingDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}
		if ($training->discontinuedId) {
			throw new BadRequestException("I don't do {$name} training anymore");
		}

		$this->training = $training;
		$this->dates = $this->trainings->getDates($this->training->trainingId);
		if (empty($this->dates)) {
			throw new BadRequestException("No dates for {$name} training", IResponse::S503_ServiceUnavailable);
		}

		$session = $this->getSession('training');
		if (!isset($session->trainingId)) {
			$this->redirect('training', $name);
		}

		if (!isset($this->dates[$session->trainingId])) {
			$date = $this->trainingDates->get($session->trainingId);
			$this->redirect('success', $date->action);
		}

		$date = $this->dates[$session->trainingId];
		if ($date->tentative) {
			$this->flashMessage($this->translator->translate('messages.trainings.submitted.tentative'));
		} else {
			$this->flashMessage($this->translator->translate('messages.trainings.submitted.confirmed'));
		}

		$this->template->name = $this->training->action;
		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.trainingapplication', [$this->training->name]);
		$this->template->title = $this->training->name;
		$this->template->description = $this->training->description;
		$this->template->lastFreeSeats = false;
		$this->template->start = $date->start;
		$this->template->end = $date->end;
		$this->template->remote = $date->remote;
		$this->template->venueCity = $date->venueCity;
		$this->template->tentative = $date->tentative;

		$upcoming = $this->trainingDates->getPublicUpcoming();
		unset($upcoming[$name]);
		$this->template->upcomingTrainings = $upcoming;

		$this->template->form = $this->createComponentApplication();
		$this->template->reviews = $this->trainingReviews->getVisibleReviews($this->training->trainingId, 3);
	}


	/**
	 * Translated locale parameters for trainings.
	 *
	 * @return array<string, array<string, string|null>>
	 */
	protected function getLocaleLinkParams(): array
	{
		if ($this->getAction() === 'default') {
			return parent::getLocaleLinkParams();
		} else {
			$params = [];
			foreach ($this->trainingLocales->getLocaleActions($this->getParameter('name')) as $key => $value) {
				$params[$key] = ['name' => $value];
			}
			return $params;
		}
	}


	private function redirectToSuccessor(?int $successorId): void
	{
		if ($successorId !== null) {
			$this->redirectPermanent('this', $this->trainings->getActionById($successorId));
		}
	}

}
