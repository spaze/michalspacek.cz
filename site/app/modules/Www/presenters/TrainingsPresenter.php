<?php
namespace App\WwwModule\Presenters;

use MichalSpacekCz\Training;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

/**
 * Trainings presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/** @var \MichalSpacekCz\Training\Applications */
	protected $trainingApplications;

	/** @var \MichalSpacekCz\Training\Mails */
	protected $trainingMails;

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;

	/** @var \MichalSpacekCz\Training\Files */
	protected $trainingFiles;

	/** @var \MichalSpacekCz\Training\Trainings */
	protected $trainings;

	/** @var \MichalSpacekCz\Training\CompanyTrainings */
	protected $companyTrainings;

	/** @var \MichalSpacekCz\Training\Locales */
	protected $trainingLocales;

	/** @var \MichalSpacekCz\Training\Reviews */
	protected $trainingReviews;

	/** @var \MichalSpacekCz\Vat */
	protected $vat;

	/** @var \Netxten\Templating\Helpers */
	protected $netxtenHelpers;

	/** @var \MichalSpacekCz\CompanyInfo\Info */
	protected $companyInfo;

	/** @var IResponse */
	protected $httpResponse;

	/** @var \Nette\Database\Row */
	private $training;

	/** @var \Nette\Database\Row[] */
	private $dates;


	/**
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \MichalSpacekCz\Training\Applications $trainingApplications
	 * @param \MichalSpacekCz\Training\Mails $trainingMails
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 * @param \MichalSpacekCz\Training\Files $trainingFiles
	 * @param \MichalSpacekCz\Training\Trainings $trainings
	 * @param \MichalSpacekCz\Training\CompanyTrainings $companyTrainings
	 * @param \MichalSpacekCz\Training\Locales $trainingLocales
	 * @param \MichalSpacekCz\Training\Reviews $trainingReviews
	 * @param \MichalSpacekCz\Vat $vat
	 * @param \Netxten\Templating\Helpers $netxtenHelpers
	 * @param \MichalSpacekCz\CompanyInfo\Info $companyInfo
	 * @param IResponse $httpResponse
	 */
	public function __construct(
		\MichalSpacekCz\Formatter\Texy $texyFormatter,
		Training\Applications $trainingApplications,
		Training\Mails $trainingMails,
		Training\Dates $trainingDates,
		Training\Files $trainingFiles,
		Training\Trainings $trainings,
		Training\CompanyTrainings $companyTrainings,
		\MichalSpacekCz\Training\Locales $trainingLocales,
		\MichalSpacekCz\Training\Reviews $trainingReviews,
		\MichalSpacekCz\Vat $vat,
		\Netxten\Templating\Helpers $netxtenHelpers,
		\MichalSpacekCz\CompanyInfo\Info $companyInfo,
		IResponse $httpResponse
	)
	{
		$this->texyFormatter = $texyFormatter;
		$this->trainingApplications = $trainingApplications;
		$this->trainingMails = $trainingMails;
		$this->trainingDates = $trainingDates;
		$this->trainingFiles = $trainingFiles;
		$this->trainings = $trainings;
		$this->companyTrainings = $companyTrainings;
		$this->trainingLocales = $trainingLocales;
		$this->trainingReviews = $trainingReviews;
		$this->vat = $vat;
		$this->netxtenHelpers = $netxtenHelpers;
		$this->companyInfo = $companyInfo;
		$this->httpResponse = $httpResponse;
		parent::__construct();
	}


	public function renderDefault()
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.trainings');
		$this->template->upcomingTrainings = $this->trainingDates->getPublicUpcoming();
		$this->template->companyTrainings = $this->companyTrainings->getWithoutPublicUpcoming();
		$this->template->lastFreeSeats = $this->trainings->lastFreeSeatsAnyTraining($this->template->upcomingTrainings);
		$this->template->discontinued = $this->trainings->getAllDiscontinued();
	}


	/**
	 * @param string $name
	 * @throws BadRequestException
	 * @throws \Nette\Application\AbortException
	 */
	public function actionTraining($name)
	{
		$this->training = $this->trainings->get($name);
		if (!$this->training) {
			throw new BadRequestException("I don't do {$name} training, yet", IResponse::S404_NOT_FOUND);
		}

		if ($this->training->successorId !== null) {
			$this->redirect('training', $this->trainings->getActionById($this->training->successorId));
		}

		$this->dates = $this->trainings->getDates($this->training->trainingId);

		$session = $this->getSession();
		$session->start();  // in createComponentApplication() it's too late as the session cookie cannot be set because the output is already sent

		$this->template->name             = $this->training->action;
		$this->template->pageTitle        = $this->texyFormatter->translate('messages.title.training', [$this->training->name]);
		$this->template->title            = $this->training->name;
		$this->template->description      = $this->training->description;
		$this->template->content          = $this->training->content;
		$this->template->upsell           = $this->training->upsell;
		$this->template->prerequisites    = $this->training->prerequisites;
		$this->template->audience         = $this->training->audience;
		$this->template->capacity         = $this->training->capacity;
		$this->template->price            = $this->training->price;
		$this->template->priceVat         = $this->vat->addVat($this->training->price);
		$this->template->studentDiscount  = $this->training->studentDiscount;
		$this->template->materials        = $this->training->materials;
		$this->template->lastFreeSeats    = $this->trainingDates->lastFreeSeatsAnyDate($this->dates);
		$this->template->dates            = $this->dates;
		$this->template->dataRetention    = 30;

		$this->template->reviews = $this->trainingReviews->getVisibleReviews($this->training->trainingId, 3);

		$this->template->loadCompanyDataVisible = $this->companyInfo->isLoadCompanyDataVisible();

		if ($this->training->discontinuedId !== null) {
			$this->template->discontinued = [$this->trainings->getDiscontinued($this->training->discontinuedId)];
			$this->httpResponse->setCode(IResponse::S410_GONE);
		}
	}


	public function actionApplication($name, $param)
	{
		$training  = $this->trainings->get($name);
		if (!$training || $training->discontinuedId) {
			throw new BadRequestException("I don't do {$name} training", IResponse::S404_NOT_FOUND);
		}

		$session = $this->getSession('training');

		$application = $this->trainingApplications->getApplicationByToken($param);
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
				$session->note
			);
			$this->redirect('training', $name);
		}

		$data                 = (array)$session->application;
		$data[$name]          = array('id' => $application->applicationId, 'dateId' => $application->dateId);
		$session->application = $data;

		$session->name         = $application->name;
		$session->email        = $application->email;
		$session->company      = $application->company;
		$session->street       = $application->street;
		$session->city         = $application->city;
		$session->zip          = $application->zip;
		$session->country      = $application->country;
		$session->companyId    = $application->companyId;
		$session->companyTaxId = $application->companyTaxId;
		$session->note         = $application->note;

		$this->redirect('training', $application->trainingAction);
	}


	protected function createComponentApplication($formName)
	{
		$form = new \MichalSpacekCz\Form\TrainingApplication($this, $formName, $this->dates, $this->translator, $this->netxtenHelpers);
		$form->setApplicationFromSession($this->session->getSection('training'));
		$form->onSuccess[] = [$this, 'submittedApplication'];
	}


	public function submittedApplication(\MichalSpacekCz\Form\TrainingApplication $form, $values)
	{
		$session = $this->getSession('training');
		$name = $form->parent->params['name'];

		try {
			$this->checkSpam($values, $name);
			$this->checkTrainingDate($values, $name);

			$date = $this->dates[$values->trainingId];
			if ($date->tentative) {
				$this->trainingApplications->addInvitation(
					$this->training,
					$values->trainingId,
					$values->name,
					$values->email,
					$values->company,
					$values->street,
					$values->city,
					$values->zip,
					$values->country,
					$values->companyId,
					$values->companyTaxId,
					$values->note
				);
			} else {
				if (isset($session->application[$name]) && $session->application[$name]['dateId'] == $values->trainingId) {
					$applicationId = $this->trainingApplications->updateApplication(
						$this->training,
						$session->application[$name]['id'],
						$values->name,
						$values->email,
						$values->company,
						$values->street,
						$values->city,
						$values->zip,
						$values->country,
						$values->companyId,
						$values->companyTaxId,
						$values->note
					);
					$session->application[$name] = null;
				} else {
					$applicationId = $this->trainingApplications->addApplication(
						$this->training,
						$values->trainingId,
						$values->name,
						$values->email,
						$values->company,
						$values->street,
						$values->city,
						$values->zip,
						$values->country,
						$values->companyId,
						$values->companyTaxId,
						$values->note
					);
				}
				$this->trainingMails->sendSignUpMail(
					$applicationId,
					$this->createTemplate(),
					$values->email,
					$values->name,
					$date->start,
					$date->end,
					$name,
					$this->training->name,
					$date->venueName,
					$date->venueNameExtended,
					$date->venueAddress,
					$date->venueCity
				);
			}
			$session->trainingId   = $values->trainingId;
			$session->name         = $values->name;
			$session->email        = $values->email;
			$session->company      = $values->company;
			$session->street       = $values->street;
			$session->city         = $values->city;
			$session->zip          = $values->zip;
			$session->country      = $values->country;
			$session->companyId    = $values->companyId;
			$session->companyTaxId = $values->companyTaxId;
			$session->note         = $values->note;
			$this->redirect('success', $name);
		} catch (\UnexpectedValueException $e) {
			\Tracy\Debugger::log($e);
			$this->flashMessage($this->translator->translate('messages.trainings.spammyapplication'), 'error');
		} catch (\PDOException $e) {
			\Tracy\Debugger::log($e, \Tracy\Debugger::ERROR);
			$this->flashMessage($this->translator->translate('messages.trainings.errorapplication'), 'error');
		}
	}


	protected function createComponentApplicationPreliminary($formName)
	{
		if ($this->training->discontinuedId) {
			throw new BadRequestException("No signups for discontinued trainings id {$this->training->discontinuedId}", IResponse::S404_NOT_FOUND);
		}
		$form = new \MichalSpacekCz\Form\TrainingApplicationPreliminary($this, $formName, $this->translator);
		$form->onSuccess[] = [$this, 'submittedApplicationPreliminary'];
	}


	public function submittedApplicationPreliminary(\MichalSpacekCz\Form\TrainingApplicationPreliminary $form, $values)
	{
		$name = $form->parent->params['name'];
		$this->trainingApplications->addPreliminaryInvitation($this->training, $values->name, $values->email);
		$this->flashMessage($this->translator->translate('messages.trainings.submitted.preliminary'));
		$this->redirect('training#' . $this->translator->translate('html.id.application'), $name);
	}


	private function checkTrainingDate(\Nette\Utils\ArrayHash $values, $name)
	{
		if (!isset($this->dates[$values->trainingId])) {
			$this->logData($values, $name);
			$message = "Training date id {$values->trainingId} is not an upcoming training, should be one of " . implode(', ', array_keys($this->dates));
			throw new \OutOfBoundsException($message);
		}
	}


	private function checkSpam(\Nette\Utils\ArrayHash $values, $name)
	{
		if (preg_match('~\s+href="\s*https?://~', $values->note)) {
			$this->logData($values, $name);
			throw new \UnexpectedValueException('Spammy note: ' . $values->note);
		}
	}


	private function logData(\Nette\Utils\ArrayHash $values, $name)
	{
		$session = $this->getSession('training');
		$logValues = $logSession = array();
		if (isset($session->application[$name])) {
			foreach ($session->application[$name] as $key => $value) {
				$logSession[] = "{$key} => \"{$value}\"";
			}
		}
		foreach ($values as $key => $value) {
			$logValues[] = "{$key} => \"{$value}\"";
		}
		$message = sprintf('Application session data for %s: %s, form values: %s',
			$name,
			(empty($logSession) ? 'empty' : implode(', ', $logSession)),
			implode(', ', $logValues)
		);
		\Tracy\Debugger::log($message);
	}


	/**
	 * @param string $name
	 * @param string|null $param
	 * @throws BadRequestException
	 */
	public function actionReviews($name, $param)
	{
		if ($param !== null) {
			throw new BadRequestException('No param here, please', IResponse::S404_NOT_FOUND);
		}

		$training = $this->trainings->get($name);
		if (!$training) {
			throw new BadRequestException("I don't do {$name} training, yet", IResponse::S404_NOT_FOUND);
		}

		$this->template->name             = $training->action;
		$this->template->pageTitle        = $this->texyFormatter->translate('messages.title.trainingreviews', [$training->name]);
		$this->template->title            = $training->name;
		$this->template->description      = $training->description;
		$this->template->reviews = $this->trainingReviews->getVisibleReviews($training->trainingId);

		if ($training->discontinuedId !== null) {
			$this->template->discontinued = [$this->trainings->getDiscontinued($training->discontinuedId)];
			$this->httpResponse->setCode(IResponse::S410_GONE);
		}
	}


	public function actionFiles($name, $param)
	{
		$session = $this->getSession('application');

		if ($param !== null) {
			$application = $this->trainingApplications->getApplicationByToken($param);
			$session->token = $param;
			$session->applicationId = ($application ? $application->applicationId : null);
			$this->redirect('files', ($application ? $application->trainingAction : $name));
		}

		if (!$session->applicationId || !$session->token) {
			throw new BadRequestException("Unknown application id, missing or invalid token", IResponse::S404_NOT_FOUND);
		}

		$training = $this->trainings->getIncludingCustom($name);
		if (!$training) {
			throw new BadRequestException("I don't do {$name} training, yet", IResponse::S404_NOT_FOUND);
		}

		$application = $this->trainingApplications->getApplicationById($session->applicationId);
		if (!$application) {
			throw new BadRequestException("No training application for id {$session->applicationId}", IResponse::S404_NOT_FOUND);
		}

		if ($application->trainingAction != $name) {
			$this->redirect('files', $application->trainingAction);
		}

		$files = $this->trainingFiles->getFiles($application->applicationId);
		$this->trainingApplications->setAccessTokenUsed($application);
		if (!$files) {
			throw new BadRequestException("No files for application id {$session->applicationId}", IResponse::S404_NOT_FOUND);
		}

		$this->template->trainingTitle = $training->name;
		$this->template->trainingName = ($training->custom ? null : $training->action);
		$this->template->trainingStart = $application->trainingStart;
		$this->template->trainingEnd = $application->trainingEnd;
		$this->template->familiar = $application->familiar;

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.trainingmaterials', [$training->name]);
		$this->template->files = $files;
	}


	/**
	 * @param string $name
	 * @param string|null $param
	 * @throws BadRequestException
	 * @throws \Nette\Application\AbortException
	 */
	public function actionSuccess($name, $param)
	{
		if ($param !== null) {
			throw new BadRequestException('No param here, please', IResponse::S404_NOT_FOUND);
		}

		$training = $this->trainings->get($name);
		if (!$training || $training->discontinuedId) {
			throw new BadRequestException("I don't do {$name} training", IResponse::S404_NOT_FOUND);
		}
		$this->dates = $this->trainings->getDates($training->trainingId);
		if (empty($this->dates)) {
			throw new BadRequestException("No dates for {$name} training", IResponse::S503_SERVICE_UNAVAILABLE);
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

		$this->template->name             = $training->action;
		$this->template->pageTitle        = $this->texyFormatter->translate('messages.title.trainingapplication', [$training->name]);
		$this->template->title            = $training->name;
		$this->template->description      = $training->description;
		$this->template->lastFreeSeats    = false;
		$this->template->start            = $date->start;
		$this->template->end              = $date->end;
		$this->template->venueCity        = $date->venueCity;
		$this->template->tentative        = $date->tentative;

		$upcoming = $this->trainingDates->getPublicUpcoming();
		unset($upcoming[$name]);
		$this->template->upcomingTrainings = $upcoming;

		$this->template->form = $this->createComponentApplication('application');
		$this->template->reviews = $this->trainingReviews->getVisibleReviews($training->trainingId, 3);
	}


	/**
	 * Translated locale parameters for trainings.
	 *
	 * @return array
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

}
