<?php
use \Nette\Application\UI\Form,
	\Nette\Diagnostics\Debugger;

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

		$this->template->upcomingTrainings = $this->context->createTrainings()->getUpcoming();
	}


	public function actionSkoleni($name)
	{
		$trainings = $this->context->createTrainings();
		$training = $trainings->get($name);

		if (!$training) {
			throw new \Nette\Application\BadRequestException("I don't do {$name} training, yet", \Nette\Http\Response::S404_NOT_FOUND);
		}

		$this->tentative[$name] = $training->tentative;

		$this->template->name             = $training->action;
		$this->template->trainingId       = $training->dateId;
		$this->template->pageTitle        = 'Školení ' . $training->name;
		$this->template->description      = $training->description;
		$this->template->content          = $training->content;
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

		$this->template->pastTrainingsMe = $trainings->getPastTrainings($name);

		$this->template->pastTrainingsJakub = $this->pastTrainingsJakub[$name];

		$this->template->reviews = $trainings->getReviews($name);

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


	public function actionSkoleniPrefill($name, $token)
	{
		$trainings = $this->context->createTrainings();
		$training  = $trainings->get($name);
		if (!$training) {
			throw new \Nette\Application\BadRequestException("I don't do {$name} training, yet", \Nette\Http\Response::S404_NOT_FOUND);
		}

		$application = $trainings->getApplicationByToken($token);
		if (!$application) {
			$this->redirect($this->getName() . ':' . $name);
		}

		$session = $this->context->session->getSection('training');

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
		$session = $this->context->session->getSection('training');

		$form = new Form($this, $formName);
		$name = $form->parent->params['name'];
		$form->setAction($form->getAction() . '#prihlaska');
		$form->addHidden('trainingId');
		$form->addHidden('date');
		$form->addGroup('Účastník');
		$form->addText('name', 'Jméno a příjmení:')
			->setDefaultValue($session->name)
			->setRequired('Zadejte prosím jméno a příjmení')
			->addRule(Form::MIN_LENGTH, 'Minimální délka jména a příjmení je %d znaky', 3)
			->addRule(Form::MAX_LENGTH, 'Maximální délka jména a příjmení je %d znaků', 100);
		$form->addText('email', 'E-mail:')
			->setDefaultValue($session->email)
			->setRequired('Zadejte prosím e-mailovou adresu')
			->addRule(Form::EMAIL, 'Zadejte platnou e-mailovou adresu')
			->addRule(Form::MAX_LENGTH, 'Maximální délka e-mailu je %d znaků', 100);

		if (!$this->tentative[$name]) {
			$form->addGroup('Fakturační údaje');
			$form->addText('company', 'Obchodní jméno:')
				->setDefaultValue($session->company)
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka obchodního jména je %d znaky', 3)
				->addRule(Form::MAX_LENGTH, 'Maximální délka obchodního jména je %d znaků', 100);
			$form->addText('street', 'Ulice a číslo:')
				->setDefaultValue($session->street)
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka ulice a čísla je %d znaky', 3)
				->addRule(Form::MAX_LENGTH, 'Maximální délka ulice a čísla je %d znaků', 100);
			$form->addText('city', 'Město:')
				->setDefaultValue($session->city)
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka města je dva znaky', 2)
				->addRule(Form::MAX_LENGTH, 'Maximální délka města je %d znaků', 100);
			$form->addText('zip', 'PSČ:')
				->setDefaultValue($session->zip)
				->addCondition(Form::FILLED)
				->addRule(Form::PATTERN, 'PSČ musí mít 5 číslic', '([0-9]\s*){5}')
				->addRule(Form::MAX_LENGTH, 'Maximální délka PSČ je %d znaků', 100);
			$form->addText('companyId', 'IČ:')
				->setDefaultValue($session->companyId)
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka IČ je %d znaky', 3)
				->addRule(Form::MAX_LENGTH, 'Maximální délka IČ je %d znaků', 100);
			$form->addText('companyTaxId', 'DIČ:')
				->setDefaultValue($session->companyTaxId)
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka DIČ je %d znaky', 3)
				->addRule(Form::MAX_LENGTH, 'Maximální délka DIČ je %d znaků', 100);
		}

		$form->setCurrentGroup(null);
		$form->addText('note', 'Poznámka:')
			->setDefaultValue($session->note)
			->addCondition(Form::FILLED)
			->addRule(Form::MAX_LENGTH, 'Maximální délka poznámky je %d znaků', 1000);
		$form->addSubmit('signUp', $this->tentative[$name] ? 'Odeslat' : 'Registrovat se');
		$form->onSuccess[] = callback($this, 'submittedApplication');

		return $form;
	}


	public function submittedApplication($form)
	{
		$session = $this->context->session->getSection('training');

		$values = $form->getValues();
		$name   = $form->parent->params['name'];
		try {
			$datetime = new DateTime();
			if ($this->tentative[$name]) {
				$this->context->createTrainings()->addInvitation(
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
				$this->context->createTrainings()->addApplication(
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


}
