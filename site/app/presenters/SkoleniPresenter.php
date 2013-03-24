<?php
use \Nette\Application\UI\Form,
	\Nette\Diagnostics\Debugger,
	\Nette\Http\Response;

/**
 * Školení presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class SkoleniPresenter extends BasePresenter
{

	/**
	 * Some training info.
	 *
	 * @var boolean
	 */
	private $tentative = array();

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


	public function renderDefault()
	{
		$this->template->pageTitle = 'Školení';
		$this->template->upcomingTrainings = $this->trainings->getUpcoming();
		$this->template->lastFreeSeats = $this->trainings->lastFreeSeatsAny($this->template->upcomingTrainings);
	}


	public function actionSkoleni($name)
	{
		$session = $this->getSession();
		$session->start();  // in createComponentApplication() it's too late as the session cookie cannot be set because the output is already sent

		$training = $this->trainings->get($name);

		if (!$training) {
			throw new \Nette\Application\BadRequestException("I don't do {$name} training, yet", Response::S404_NOT_FOUND);
		}

		$this->tentative[$name] = $training->tentative;

		$this->template->name             = $training->action;
		$this->template->trainingId       = $training->dateId;
		$this->template->pageTitle        = 'Školení ' . $training->name;
		$this->template->title            = $training->name;
		$this->template->description      = $training->description;
		$this->template->content          = $training->content;
		$this->template->upsell           = $training->upsell;
		$this->template->prerequisites    = $training->prerequisites;
		$this->template->audience         = $training->audience;
		$this->template->start            = $training->start;
		$this->template->end              = $training->end;
		$this->template->tentative        = $training->tentative;
		$this->template->originalHref     = $training->originalHref;
		$this->template->capacity         = $training->capacity;
		$this->template->services         = $training->services;
		$this->template->price            = $training->price;
		$this->template->studentDiscount  = $training->studentDiscount;
		$this->template->materials        = $training->materials;
		$this->template->venueHref        = $training->venueHref;
		$this->template->venueName        = $training->venueName;
		$this->template->venueAddress     = $training->venueAddress;
		$this->template->venueDescription = $training->venueDescription;
		$this->template->lastFreeSeats    = $training->lastFreeSeats;

		$this->template->pastTrainingsMe = $this->trainings->getPastTrainings($name);

		$this->template->pastTrainingsJakub = $this->pastTrainingsJakub[$name];

		$this->template->reviews = $this->trainings->getReviews($name, 3);

		// hide the form when all the flash message are not errors
		$this->template->showForm = true;
		foreach ($this->template->flashes as $flash) {
			$this->template->showForm = false;
			if ($flash->type == 'error') {
				$this->template->showForm = true;
				break;
			}
		}

		// we want to access form fields' labels and values even when not actually showing the form
		if (!$this->template->showForm) {
			$this->template->form = $this->createComponentApplication('application');
		}
	}


	public function actionPrihlaska($name, $param)
	{
		$training  = $this->trainings->get($name);
		if (!$training) {
			throw new \Nette\Application\BadRequestException("I don't do {$name} training, yet", Response::S404_NOT_FOUND);
		}

		$session = $this->getSession('training');

		$application = $this->trainings->getApplicationByToken($param);
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
			$this->redirect($this->getName() . ':' . $name);
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

		$this->redirect($this->getName() . ':' . $application->action);
	}


	protected function createComponentApplication($formName)
	{
		$session = $this->getSession('training');

		$form = new Form($this, $formName);
		$name = $form->parent->params['name'];
		$form->setAction($form->getAction() . '#prihlaska');
		$form->addHidden('trainingId');  // trainingId is actually dateId, oh well
		$form->addGroup('Účastník');
		$form->addText('name', 'Jméno a příjmení:')
			->setDefaultValue($session->name)
			->setRequired('Zadejte prosím jméno a příjmení')
			->addRule(Form::MIN_LENGTH, 'Minimální délka jména a příjmení je %d znaky', 3)
			->addRule(Form::MAX_LENGTH, 'Maximální délka jména a příjmení je %d znaků', 200);
		$form->addText('email', 'E-mail:')
			->setDefaultValue($session->email)
			->setRequired('Zadejte prosím e-mailovou adresu')
			->addRule(Form::EMAIL, 'Zadejte platnou e-mailovou adresu')
			->addRule(Form::MAX_LENGTH, 'Maximální délka e-mailu je %d znaků', 200);

		if (!$this->tentative[$name]) {
			$form->addGroup('Fakturační údaje');
			$form->addText('company', 'Obchodní jméno:')
				->setDefaultValue($session->company)
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka obchodního jména je %d znaky', 3)
				->addRule(Form::MAX_LENGTH, 'Maximální délka obchodního jména je %d znaků', 200);
			$form->addText('street', 'Ulice a číslo:')
				->setDefaultValue($session->street)
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka ulice a čísla je %d znaky', 3)
				->addRule(Form::MAX_LENGTH, 'Maximální délka ulice a čísla je %d znaků', 200);
			$form->addText('city', 'Město:')
				->setDefaultValue($session->city)
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka města je %d znaky', 2)
				->addRule(Form::MAX_LENGTH, 'Maximální délka města je %d znaků', 200);
			$form->addText('zip', 'PSČ:')
				->setDefaultValue($session->zip)
				->addCondition(Form::FILLED)
				->addRule(Form::PATTERN, 'PSČ musí mít 5 číslic', '([0-9]\s*){5}')
				->addRule(Form::MAX_LENGTH, 'Maximální délka PSČ je %d znaků', 200);
			$form->addText('companyId', 'IČ:')
				->setDefaultValue($session->companyId)
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka IČ je %d znaky', 3)
				->addRule(Form::MAX_LENGTH, 'Maximální délka IČ je %d znaků', 200);
			$form->addText('companyTaxId', 'DIČ:')
				->setDefaultValue($session->companyTaxId)
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka DIČ je %d znaky', 3)
				->addRule(Form::MAX_LENGTH, 'Maximální délka DIČ je %d znaků', 200);
		}

		$form->setCurrentGroup(null);
		$form->addText('note', 'Poznámka:')
			->setDefaultValue($session->note)
			->addCondition(Form::FILLED)
			->addRule(Form::MAX_LENGTH, 'Maximální délka poznámky je %d znaků', 2000);
		$form->addSubmit('signUp', $this->tentative[$name] ? 'Odeslat' : 'Registrovat se');
		$form->onSuccess[] = new \Nette\Callback($this, 'submittedApplication');

		return $form;
	}


	public function submittedApplication($form)
	{
		$session = $this->getSession('training');

		$values = $form->getValues();
		$name   = $form->parent->params['name'];

		$upcoming = $this->trainings->getUpcoming();
		if (!isset($upcoming[$values['trainingId']])) {
			$logValues = $logSession = array();
			if (isset($session->application[$name])) {
				foreach ($session->application[$name] as $key => $value) {
					$logSession[] = "{$key} => \"{$value}\"";
				}
			}
			foreach ($values as $key => $value) {
				$logValues[] = "{$key} => \"{$value}\"";
			}
			Debugger::log(sprintf('Training date id %s is not an upcoming training, should be one of %s (application session data for %s: %s, form values: %s)',
				$values['trainingId'],
				implode(', ', array_keys($upcoming)),
				$name,
				(empty($logSession) ? 'empty' : implode(', ', $logSession)),
				implode(', ', $logValues)
			));
			$this->flashMessage('Je mi líto, ale v zadané datum se žádné školení nekoná. Pokud to chcete změnit, napište mi!', 'error');
			return;
		}

		try {
			$datetime = new DateTime();
			if ($this->tentative[$name]) {
				$this->trainings->addInvitation(
					$values['trainingId'],
					$values['name'],
					$values['email'],
					$values['note']
				);
				$this->flashMessage('Díky, přihláška odeslána! Dám vám vědět, jakmile budu znát přesný termín.');
				$session->name  = $values['name'];
				$session->email = $values['email'];
				$session->note  = $values['note'];
			} else {
				if (isset($session->application[$name]) && $session->application[$name]['dateId'] == $values['trainingId']) {
					$this->trainings->updateApplication(
						$session->application[$name]['id'],
						$values['name'],
						$values['email'],
						$values['company'],
						$values['street'],
						$values['city'],
						$values['zip'],
						$values['companyId'],
						$values['companyTaxId'],
						$values['note']
					);
					$session->application[$name] = null;
				} else {
					$this->trainings->addApplication(
						$values['trainingId'],
						$values['name'],
						$values['email'],
						$values['company'],
						$values['street'],
						$values['city'],
						$values['zip'],
						$values['companyId'],
						$values['companyTaxId'],
						$values['note']
					);
				}
				$this->flashMessage('Díky, přihláška odeslána! Potvrzení společně s fakturou vám přijde do druhého pracovního dne.');
				$session->name         = $values['name'];
				$session->email        = $values['email'];
				$session->company      = $values['company'];
				$session->street       = $values['street'];
				$session->city         = $values['city'];
				$session->zip          = $values['zip'];
				$session->companyId    = $values['companyId'];
				$session->companyTaxId = $values['companyTaxId'];
				$session->note         = $values['note'];
			}
			$this->redirect($this->getName() . ':' . $name);
		} catch (PDOException $e) {
			Debugger::log($e, Debugger::ERROR);
			$this->flashMessage('Ups, něco se rozbilo a přihlášku se nepodařilo odeslat, zkuste to za chvíli znovu.', 'error');
		}
	}


	public function actionOhlasy($name, $param)
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


	public function actionSoubory($name, $param)
	{
		$session = $this->getSession('application');

		if ($param !== null) {
			$application = $this->trainings->getApplicationByToken($param);
			$session->token = $param;
			$session->applicationId = ($application ? $application->applicationId : null);
			$this->redirect($this->getName() . ':soubory', $application->action);
		}

		if (!$session->applicationId || !$session->token) {
			throw new \Nette\Application\BadRequestException("Unknown application id, missing or invalid token", Response::S404_NOT_FOUND);
		}

		$training = $this->trainings->get($name);
		if (!$training) {
			throw new \Nette\Application\BadRequestException("I don't do {$name} training, yet", Response::S404_NOT_FOUND);
		}

		$application = $this->trainings->getApplicationById($session->applicationId);
		if (!$application) {
			throw new \Nette\Application\BadRequestException("No training application for id {$session->applicationId}", Response::S404_NOT_FOUND);
		}

		$files = $this->trainings->getFiles($session->applicationId);
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


}
