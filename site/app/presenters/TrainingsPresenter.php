<?php
use \Nette\Application\UI\Form,
	\Nette\Diagnostics\Debugger,
	\Nette\Http\Response,
	\MichalSpacekCz\TrainingApplications;

/**
 * Trainings presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Files */
	protected $files;

	/** @var \MichalSpacekCz\TrainingApplications */
	protected $trainingApplications;

	/** @var \MichalSpacekCz\TrainingMails */
	protected $trainingMails;

	/** @var \MichalSpacekCz\Trainings */
	protected $trainings;

	/** @var \MichalSpacekCz\Vat */
	protected $vat;

	/** @var \Nette\Database\Row */
	private $training;

	/** @var array */
	private $dates;

	/**
	 * Past trainings done by Jakub.
	 *
	 * @var array
	 */
	protected $pastTrainingsJakub = array(
		'uvodDoPhp' => array(
			'2011-09-07', '2011-04-20',
			'2010-12-01', '2010-03-02',
			'2009-12-01', '2009-09-14', '2009-06-25', '2009-04-22', '2009-01-20',
			'2008-12-02', '2008-10-13', '2008-02-29',
			'2007-10-25', '2007-02-26',
		),
		'programovaniVPhp5' => array(
			'2011-09-08', '2011-04-21',
			'2010-12-02', '2010-06-08', '2010-03-03',
			'2009-12-02', '2009-09-29', '2009-09-15', '2009-06-26', '2009-04-23', '2009-01-21',
			'2008-12-03', '2008-10-14', '2008-04-08',
			'2007-10-26',
			'2006-11-16', '2006-06-12',
		),
		'bezpecnostPhpAplikaci' => array(
			'2011-09-16', '2011-09-05', '2011-04-29',
			'2010-12-09', '2010-10-08', '2010-06-11', '2010-03-12', '2010-03-09',
			'2009-12-08', '2009-09-17', '2009-06-08', '2009-03-12', '2009-03-10',
			'2008-12-08', '2008-10-21', '2008-06-24', '2008-02-28', '2008-02-25',
			'2007-10-29', '2007-10-23', '2007-06-26', '2007-04-16',
			'2006-10-27', '2006-06-22', '2006-04-25',
		),
		'vykonnostWebovychAplikaci' => array(
			'2011-09-14', '2011-04-27',
			'2010-12-07', '2010-03-10',
			'2009-09-21', '2009-03-11',
			'2008-12-09', '2008-10-22', '2008-06-27',
		),
	);


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\Files
	 * @param \MichalSpacekCz\TrainingApplications $trainingApplications
	 * @param \MichalSpacekCz\TrainingMails $trainingMails
	 * @param \MichalSpacekCz\Trainings $trainings
	 * @param \MichalSpacekCz\Vat $vat
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		\MichalSpacekCz\Files $files,
		\MichalSpacekCz\TrainingApplications $trainingApplications,
		\MichalSpacekCz\TrainingMails $trainingMails,
		\MichalSpacekCz\Trainings $trainings,
		\MichalSpacekCz\Vat $vat
	)
	{
		$this->files = $files;
		$this->trainingApplications = $trainingApplications;
		$this->trainingMails = $trainingMails;
		$this->trainings = $trainings;
		$this->vat = $vat;
		parent::__construct($translator);
	}


	public function renderDefault()
	{
		$this->template->pageTitle = 'Školení';
		$this->template->upcomingTrainings = $this->trainings->getPublicUpcoming();
		$this->template->lastFreeSeats = $this->trainings->lastFreeSeatsAnyTraining($this->template->upcomingTrainings);
	}


	public function actionTraining($name)
	{
		$session = $this->getSession();
		$session->start();  // in createComponentApplication() it's too late as the session cookie cannot be set because the output is already sent

		$this->training = $this->trainings->get($name);
		if (!$this->training) {
			throw new \Nette\Application\BadRequestException("I don't do {$name} training, yet", Response::S404_NOT_FOUND);
		}
		$this->dates = $this->trainings->getDates($name);
		if (empty($this->dates)) {
			throw new \Nette\Application\BadRequestException("No dates for {$name} training", Response::S503_SERVICE_UNAVAILABLE);
		}

		$this->template->name             = $this->training->action;
		$this->template->pageTitle        = 'Školení ' . $this->training->name;
		$this->template->title            = $this->training->name;
		$this->template->description      = $this->training->description;
		$this->template->content          = $this->training->content;
		$this->template->upsell           = $this->training->upsell;
		$this->template->prerequisites    = $this->training->prerequisites;
		$this->template->audience         = $this->training->audience;
		$this->template->originalHref     = $this->training->originalHref;
		$this->template->capacity         = $this->training->capacity;
		$this->template->price            = $this->training->price;
		$this->template->priceVat         = $this->vat->addVat($this->training->price);
		$this->template->studentDiscount  = $this->training->studentDiscount;
		$this->template->materials        = $this->training->materials;
		$this->template->lastFreeSeats    = $this->trainings->lastFreeSeatsAnyDate($this->dates);
		$this->template->dates            = $this->dates;

		$this->template->pastTrainingsMe = $this->trainings->getPastDates($name);

		$this->template->pastTrainingsJakub = $this->pastTrainingsJakub[$name];

		$this->template->reviews = $this->trainings->getReviews($name, 3);
	}


	public function actionApplication($name, $param)
	{
		$training  = $this->trainings->get($name);
		if (!$training) {
			throw new \Nette\Application\BadRequestException("I don't do {$name} training, yet", Response::S404_NOT_FOUND);
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
		$session->companyId    = $application->companyId;
		$session->companyTaxId = $application->companyTaxId;
		$session->note         = $application->note;

		$this->redirect('training', $application->action);
	}


	protected function createComponentApplication($formName)
	{
		$rules = $this->trainingApplications->getDataRules();

		$dates = array();
		$helpers = new \Bare\Next\Templating\Helpers();
		foreach ($this->dates as $date) {
			if ($date->tentative) {
				$start = $helpers->localDate($date->start, 'cs', '%B %Y');
			} else {
				$start = \Nette\Templating\Helpers::date($date->start, 'j. n. Y');
			}
			$dates[$date->dateId] = "{$start} {$date->venueCity}" . ($date->tentative ? ' (předběžný termín)' : '');
		}

		$session = $this->getSession('training');

		$form = new Form($this, $formName);
		$name = $form->parent->params['name'];
		$label = 'Termín školení:';
		// trainingId is actually dateId, oh well
		if (count($this->dates) > 1) {
			$form->addSelect('trainingId', $label, $dates)
				->setRequired('Vyberte prosím termín a místo školení')
				->setPrompt('- vyberte termín a místo -');
		} else {
			$field = new \Bare\Next\Forms\Controls\HiddenFieldWithLabel($label, $date->dateId, $dates[$date->dateId]);
			$form->addComponent($field, 'trainingId');
		}
		$form->addGroup('Účastník');
		$form->addText('name', 'Jméno a příjmení:')
			->setDefaultValue($session->name)
			->setRequired('Zadejte prosím jméno a příjmení')
			->addRule(Form::MIN_LENGTH, 'Minimální délka jména a příjmení je %d znaky', $rules['name'][Form::MIN_LENGTH])
			->addRule(Form::MAX_LENGTH, 'Maximální délka jména a příjmení je %d znaků', $rules['name'][Form::MAX_LENGTH]);
		$form->addText('email', 'E-mail:')
			->setDefaultValue($session->email)
			->setRequired('Zadejte prosím e-mailovou adresu')
			->addRule(Form::EMAIL, 'Zadejte platnou e-mailovou adresu')
			->addRule(Form::MAX_LENGTH, 'Maximální délka e-mailu je %d znaků', $rules['email'][Form::MAX_LENGTH]);

		$form->addGroup('Fakturační údaje');
		$form->addText('company', 'Obchodní jméno:')
			->setDefaultValue($session->company)
			->addCondition(Form::FILLED)
			->addRule(Form::MIN_LENGTH, 'Minimální délka obchodního jména je %d znaky', $rules['company'][Form::MIN_LENGTH])
			->addRule(Form::MAX_LENGTH, 'Maximální délka obchodního jména je %d znaků', $rules['company'][Form::MAX_LENGTH]);
		$form->addText('street', 'Ulice a číslo:')
			->setDefaultValue($session->street)
			->addCondition(Form::FILLED)
			->addRule(Form::MIN_LENGTH, 'Minimální délka ulice a čísla je %d znaky', $rules['street'][Form::MIN_LENGTH])
			->addRule(Form::MAX_LENGTH, 'Maximální délka ulice a čísla je %d znaků', $rules['street'][Form::MAX_LENGTH]);
		$form->addText('city', 'Město:')
			->setDefaultValue($session->city)
			->addCondition(Form::FILLED)
			->addRule(Form::MIN_LENGTH, 'Minimální délka města je %d znaky', $rules['city'][Form::MIN_LENGTH])
			->addRule(Form::MAX_LENGTH, 'Maximální délka města je %d znaků', $rules['city'][Form::MAX_LENGTH]);
		$form->addText('zip', 'PSČ:')
			->setDefaultValue($session->zip)
			->addCondition(Form::FILLED)
			->addRule(Form::PATTERN, 'PSČ musí mít 5 číslic', $rules['zip'][Form::PATTERN])
			->addRule(Form::MAX_LENGTH, 'Maximální délka PSČ je %d znaků', $rules['zip'][Form::MAX_LENGTH]);
		$form->addText('companyId', 'IČ:')
			->setDefaultValue($session->companyId)
			->addCondition(Form::FILLED)
			->addRule(Form::MIN_LENGTH, 'Minimální délka IČ je %d znaky', $rules['companyId'][Form::MIN_LENGTH])
			->addRule(Form::MAX_LENGTH, 'Maximální délka IČ je %d znaků', $rules['companyId'][Form::MAX_LENGTH]);
		$form->addText('companyTaxId', 'DIČ:')
			->setDefaultValue($session->companyTaxId)
			->addCondition(Form::FILLED)
			->addRule(Form::MIN_LENGTH, 'Minimální délka DIČ je %d znaky', $rules['companyTaxId'][Form::MIN_LENGTH])
			->addRule(Form::MAX_LENGTH, 'Maximální délka DIČ je %d znaků', $rules['companyTaxId'][Form::MAX_LENGTH]);

		$form->setCurrentGroup(null);
		$form->addText('note', 'Poznámka:')
			->setDefaultValue($session->note)
			->addCondition(Form::FILLED)
			->addRule(Form::MAX_LENGTH, 'Maximální délka poznámky je %d znaků', $rules['note'][Form::MAX_LENGTH]);
		$form->addSubmit('signUp', 'Odeslat');
		$form->onSuccess[] = $this->submittedApplication;

		return $form;
	}


	public function submittedApplication($form)
	{
		$session = $this->getSession('training');

		$values = $form->getValues();
		$name   = $form->parent->params['name'];

		try {
			$this->checkSpam($values, $name);
			$this->checkTrainingDate($values, $name);

			$date = $this->dates[$values->trainingId];
			if ($date->tentative) {
				$this->trainingApplications->addInvitation(
					$values->trainingId,
					$values->name,
					$values->email,
					$values->company,
					$values->street,
					$values->city,
					$values->zip,
					$values->companyId,
					$values->companyTaxId,
					$values->note
				);
			} else {
				if (isset($session->application[$name]) && $session->application[$name]['dateId'] == $values->trainingId) {
					$applicationId = $this->trainingApplications->updateApplication(
						$session->application[$name]['id'],
						$values->name,
						$values->email,
						$values->company,
						$values->street,
						$values->city,
						$values->zip,
						$values->companyId,
						$values->companyTaxId,
						$values->note
					);
					$session->application[$name] = null;
				} else {
					$applicationId = $this->trainingApplications->addApplication(
						$values->trainingId,
						$values->name,
						$values->email,
						$values->company,
						$values->street,
						$values->city,
						$values->zip,
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
			$session->companyId    = $values->companyId;
			$session->companyTaxId = $values->companyTaxId;
			$session->note         = $values->note;
			$this->redirect('success', $name);
		} catch (\UnexpectedValueException $e) {
			Debugger::log($e);
			$this->flashMessage('Přihláška vypadá jako spam, takhle by to nešlo.', 'error');
		} catch (\PDOException $e) {
			Debugger::log($e, Debugger::ERROR);
			$this->flashMessage('Ups, něco se rozbilo a přihlášku se nepodařilo odeslat, zkuste to za chvíli znovu.', 'error');
		}
	}


	private function checkTrainingDate(\Nette\ArrayHash $values, $name)
	{
		if (!isset($this->dates[$values->trainingId])) {
			$this->logData($values, $name);
			$message = "Training date id {$values->trainingId} is not an upcoming training, should be one of " . implode(', ', $dateIds);
			throw new \OutOfBoundsException($message);
		}
	}


	private function checkSpam(\Nette\ArrayHash $values, $name)
	{
		if (preg_match('~\s+href="\s*https?://~', $values->note)) {
			$this->logData($values, $name);
			throw new \UnexpectedValueException('Spammy note: ' . $values->note);
		}
	}


	private function logData(\Nette\ArrayHash $values, $name)
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
		Debugger::log($message);
	}


	public function actionReviews($name, $param)
	{
		if ($param !== null) {
			throw new \Nette\Application\BadRequestException('No param here, please', Response::S404_NOT_FOUND);
		}

		$training = $this->trainings->get($name);
		if (!$training) {
			throw new \Nette\Application\BadRequestException("I don't do {$name} training, yet", Response::S404_NOT_FOUND);
		}

		$this->template->name             = $training->action;
		$this->template->pageTitle        = 'Ohlasy na školení ' . $training->name;
		$this->template->title            = $training->name;
		$this->template->description      = $training->description;
		$this->template->reviews = $this->trainings->getReviews($name);
	}


	public function actionFiles($name, $param)
	{
		$session = $this->getSession('application');

		if ($param !== null) {
			$application = $this->trainingApplications->getApplicationByToken($param);
			$session->token = $param;
			$session->applicationId = ($application ? $application->applicationId : null);
			$this->redirect('files', ($application ? $application->action : $name));
		}

		if (!$session->applicationId || !$session->token) {
			throw new \Nette\Application\BadRequestException("Unknown application id, missing or invalid token", Response::S404_NOT_FOUND);
		}

		$training = $this->trainings->get($name);
		if (!$training) {
			throw new \Nette\Application\BadRequestException("I don't do {$name} training, yet", Response::S404_NOT_FOUND);
		}

		$application = $this->trainingApplications->getApplicationById($session->applicationId);
		if (!$application) {
			throw new \Nette\Application\BadRequestException("No training application for id {$session->applicationId}", Response::S404_NOT_FOUND);
		}

		if ($application->action != $name) {
			$this->redirect('files', $application->action);
		}

		$files = $this->trainingApplications->getFiles($application->applicationId);
		$this->trainingApplications->setAccessTokenUsed($application);
		if (!$files) {
			throw new \Nette\Application\BadRequestException("No files for application id {$session->applicationId}", Response::S404_NOT_FOUND);
		}
		foreach ($files as $file) {
			$file->info = $this->files->getInfo("{$file->dirName}/{$file->fileName}");
		}

		$this->template->trainingTitle = $training->name;
		$this->template->trainingName = $training->action;
		$this->template->trainingDate = $application->trainingStart;

		$this->template->pageTitle = 'Materiály ze školení ' . $training->name;
		$this->template->files = $files;
	}


	public function actionSuccess($name, $param)
	{
		if ($param !== null) {
			throw new \Nette\Application\BadRequestException('No param here, please', Response::S404_NOT_FOUND);
		}

		$training = $this->trainings->get($name);
		if (!$training) {
			throw new \Nette\Application\BadRequestException("I don't do {$name} training, yet", Response::S404_NOT_FOUND);
		}
		$this->dates = $this->trainings->getDates($name);
		if (empty($this->dates)) {
			throw new \Nette\Application\BadRequestException("No dates for {$name} training", Response::S503_SERVICE_UNAVAILABLE);
		}

		$session = $this->getSession('training');
		if (!isset($session->trainingId)) {
			$this->redirect('training', $name);
		}

		$date = $this->dates[$session->trainingId];
		if ($date->tentative) {
			$this->flashMessage('Díky, přihláška odeslána! Dám vám vědět, jakmile budu znát přesný termín.');
		} else {
			$this->flashMessage('Díky, přihláška odeslána! Potvrzení přijde za chvíli e-mailem, fakturu zašlu během několika dní.');
		}

		$this->template->name             = $training->action;
		$this->template->pageTitle        = 'Přihláška na ' . $training->name;
		$this->template->title            = $training->name;
		$this->template->description      = $training->description;
		$this->template->lastFreeSeats    = false;
		$this->template->start            = $date->start;
		$this->template->venueCity        = $date->venueCity;
		$this->template->tentative        = $date->tentative;

		$upcoming = $this->trainings->getPublicUpcoming();
		unset($upcoming[$name]);
		$this->template->upcomingTrainings = $upcoming;

		$this->template->form = $this->createComponentApplication('application');
	}


}
