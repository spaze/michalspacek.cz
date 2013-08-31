<?php
namespace AdminModule;

use \Nette\Application\UI\Form;
use \Nette\Utils\Html;

/**
 * Ucastnici presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class UcastniciPresenter extends BasePresenter
{

	/** @var array */
	private $dates;

	/** @var array */
	private $applications;

	/** @var array */
	private $applicationIdsAttended;

	/** @var \Nette\Database\Row */
	private $application;

	private $applicationId;

	/** @var \Nette\Database\Row */
	private $review;

	/** @var \Nette\Database\Row */
	private $training;

	private $dateId;

	private $redirectParam;


	private function addDate($form, $name, $label, $required = true, $defaultValue = null)
	{
		$text = $form->addText($name, $label);
		if ($defaultValue) {
			$text->setDefaultValue($defaultValue);
		}
		$text
			->setAttribute('placeholder', 'YYYY-MM-DD HH:MM:SS nebo NOW')
			->setAttribute('title', 'Formát  YYYY-MM-DD HH:MM:SS nebo NOW')
			->addCondition(Form::FILLED)
			->addRule(Form::PATTERN, 'Datum musí být ve formátu YYYY-MM-DD HH:MM:SS nebo NOW', '(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})|[Nn][Oo][Ww]');
		if ($required) {
			$text->setRequired('Zadejte datum');
		}
	}


	public function actionTermin($param)
	{
		$this->redirectParam = $param;
		$this->training = $this->trainings->getByDate($param);
		$this->applications = $this->trainingApplications->getByDate($param);
		$attendedStatuses = $this->trainingApplications->getAttendedStatuses();
		$discardedStatuses = $this->trainingApplications->getDiscardedStatuses();
		foreach ($this->applications as $application) {
			$application->discarded = in_array($application->status, $discardedStatuses);
			$application->attended = in_array($application->status, $attendedStatuses);
			if ($application->attended) {
				$this->applicationIdsAttended[] = $application->id;
			}
			$application->childrenStatuses = $this->trainingApplications->getChildrenStatuses($application->status);
		}

		$this->template->pageTitle     = 'Účastníci zájezdu';
		$this->template->trainingStart = $this->training->start;
		$this->template->trainingName  = $this->training->name;
		$this->template->applications  = $this->applications;
		$this->template->attendedStatuses = $attendedStatuses;
	}


	public function actionSoubory($param)
	{
		$this->redirectParam = $param;
		$application = $this->trainingApplications->getApplicationById($param);
		if (!in_array($application->status, $this->trainingApplications->getAttendedStatuses())) {
			$this->redirect('termin', $application->dateId);
		}

		$this->applicationIdsAttended = array($application->applicationId);

		$files = $this->trainingApplications->getFiles($param);
		foreach ($files as $file) {
			$file->exists = file_exists("{$file->dirName}/{$file->fileName}");
		}

		$this->training = $this->trainings->getByDate($application->dateId);

		$this->template->pageTitle = 'Soubory';
		$this->template->files     = $files;
		$this->template->trainingStart = $this->training->start;
		$this->template->trainingName  = $this->training->name;
		$this->template->trainingCity  = $this->training->venueCity;
		$this->template->name          = $application->name;
		$this->template->dateId        = $application->dateId;
	}


	public function actionOhlasy($param)
	{
		$this->applicationId = $param;
		$this->review = $this->trainings->getReviewByApplicationId($this->applicationId);

		$date = $this->trainings->getByDate($this->review->dateId);

		$this->template->pageTitle          = 'Ohlasy';
		$this->template->applicationName    = $this->review->applicationName;
		$this->template->applicationCompany = $this->review->applicationCompany;
		$this->template->trainingStart      = $date->start;
		$this->template->trainingName       = $date->name;
		$this->template->trainingCity  = $date->venueCity;
		$this->template->name          = $this->review->applicationName;
		$this->template->dateId        = $this->review->dateId;
	}


	public function actionPrihlaska($param)
	{
		$this->applicationId = $param;
		$this->application = $this->trainingApplications->getApplicationById($this->applicationId);
		$this->dateId = $this->application->dateId;
		$training = $this->trainings->getByDate($this->application->dateId);

		$this->template->pageTitle     = "{$this->application->name}";
		$this->template->applicationId = $this->applicationId;
		$this->template->dateId        = $this->dateId;
		$this->template->status        = $this->application->status;
		$this->template->statusTime    = $this->application->statusTime;
		$this->template->trainingName  = $training->name;
		$this->template->trainingStart = $training->start;
		$this->template->trainingCity  = $training->venueCity;
		$this->template->attended      = in_array($this->application->status, $this->trainingApplications->getAttendedStatuses());
	}


	public function renderDefault()
	{
		$this->template->pageTitle = 'Import';
		$this->template->trainings = $this->trainings->getAllTrainings();
		$this->template->now = new \DateTime();

		$upcomingIds = array();
		foreach ($this->trainings->getUpcoming() as $training) {
			foreach ($training->dates as $date) {
				$upcomingIds[] = $date->dateId;
			}
		}
		$this->template->upcomingIds = $upcomingIds;
	}


	protected function createComponentStatuses($formName)
	{
		$form = new Form($this, $formName);
		$container = $form->addContainer('applications');

		foreach ($this->applications as $application) {
			$select = $container->addSelect($application->id, 'Status')
				->setPrompt('- změnit na -')
				->setItems($application->childrenStatuses, false);
			if (empty($application->childrenStatuses)) {
				$select->setDisabled()
					->setPrompt('nelze dále měnit');
			}
		}

		$this->addDate($form, 'date', 'Datum:');
		$form->addSubmit('submit', 'Změnit');
		$form->onSuccess[] = new \Nette\Callback($this, 'submittedStatuses');

		return $form;
	}


	public function submittedStatuses($form)
	{
		$values = $form->getValues();
		foreach ($values->applications as $id => $status) {
			if ($status) {
				$this->trainingApplications->setStatus($id, $status, $values->date);
			}
		}
		$this->redirect($this->getAction(), $this->redirectParam);
	}


	protected function createComponentApplications($formName)
	{
		$rules = $this->trainingApplications->getDataRules();

		$sources = array();
		foreach ($this->trainingApplications->getTrainingApplicationSources() as $source) {
			$sources[$source->alias] = $source->name;
		}

		$statuses = array();
		foreach ($this->trainingApplications->getInitialStatuses() as $status) {
			$statuses[$status] = $status;
		}

		$form = new Form($this, $formName);

		$applicationsContainer = $form->addContainer('applications');
		$count = (isset($_POST['applications']) ? count($_POST['applications']) : 1);
		for ($i = 0; $i < $count; $i++) {
			$dataContainer = $applicationsContainer->addContainer($i);
			$dataContainer->addText('name', 'Jméno')
				->setRequired('Zadejte prosím jméno')
				->addRule(Form::MIN_LENGTH, 'Minimální délka jména je %d znaky', $rules['name'][Form::MIN_LENGTH])
				->addRule(Form::MAX_LENGTH, 'Maximální délka jména je %d znaků', $rules['name'][Form::MAX_LENGTH]);
			$dataContainer->addText('email', 'E-mail')
				->setRequired('Zadejte prosím e-mailovou adresu')
				->addRule(Form::EMAIL, 'Zadejte platnou e-mailovou adresu')
				->addRule(Form::MAX_LENGTH, 'Maximální délka e-mailu je %d znaků', $rules['email'][Form::MAX_LENGTH]);
			$dataContainer->addText('company', 'Společnost')
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka společnosti je %d znaky', $rules['company'][Form::MIN_LENGTH])
				->addRule(Form::MAX_LENGTH, 'Maximální délka společnosti je %d znaků', $rules['company'][Form::MAX_LENGTH]);
			$dataContainer->addText('street', 'Ulice')
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka ulice je %d znaky', $rules['street'][Form::MIN_LENGTH])
				->addRule(Form::MAX_LENGTH, 'Maximální délka ulice je %d znaků', $rules['street'][Form::MAX_LENGTH]);
			$dataContainer->addText('city', 'Město')
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka města je %d znaky', $rules['city'][Form::MIN_LENGTH])
				->addRule(Form::MAX_LENGTH, 'Maximální délka města je %d znaků', $rules['city'][Form::MAX_LENGTH]);
			$dataContainer->addText('zip', 'PSČ')
				->addCondition(Form::FILLED)
				->addRule(Form::PATTERN, 'PSČ musí mít 5 číslic', $rules['zip'][Form::PATTERN])
				->addRule(Form::MAX_LENGTH, 'Maximální délka PSČ je %d znaků', $rules['zip'][Form::MAX_LENGTH]);
			$dataContainer->addText('companyId', 'IČ')
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka IČ je %d znaky', $rules['companyId'][Form::MIN_LENGTH])
				->addRule(Form::MAX_LENGTH, 'Maximální délka IČ je %d znaků', $rules['companyId'][Form::MAX_LENGTH]);
			$dataContainer->addText('companyTaxId', 'DIČ')
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka DIČ je %d znaky', $rules['companyTaxId'][Form::MIN_LENGTH])
				->addRule(Form::MAX_LENGTH, 'Maximální délka DIČ je %d znaků', $rules['companyTaxId'][Form::MAX_LENGTH]);
			$dataContainer->addText('note', 'Poznámka')
				->addCondition(Form::FILLED)
				->addRule(Form::MAX_LENGTH, 'Maximální délka poznámky je %d znaků', $rules['note'][Form::MAX_LENGTH]);
		}

		$this->addDate($form, 'date', 'Datum:');
		$form->addSelect('status', 'Status:', $statuses)
			->setRequired('Vyberte status')
			->setPrompt('- vyberte status -');
		$form->addSelect('source', 'Zdroj:', $sources)
			->setRequired('Vyberte zdroj')
			->setPrompt('- vyberte zdroj -');

		$form->addSubmit('submit', 'Přidat');
		$form->onSuccess[] = new \Nette\Callback($this, 'submittedApplications');

		return $form;
	}


	private function findDate($dateId)
	{
		foreach ($this->dates as $training) {
			foreach ($training->dates as $date) {
				if ($date->dateId == $dateId) {
					return $date;
				}
			}
		}
		return false;
	}


	public function submittedApplications($form)
	{
		$values = $form->getValues();
		foreach ($values->applications as $application) {
			$this->trainingApplications->insertApplication(
				$this->dateId,
				$application->name,
				$application->email,
				$application->company,
				$application->street,
				$application->city,
				$application->zip,
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


	protected function createComponentReview($formName)
	{
		$form = new Form($this, $formName);
		$form->addCheckbox('overwriteName', 'Přepsat jméno:')
			->setDefaultValue($this->review->name != null);
		$form->addText('name', 'Jméno:')
			->setDefaultValue($this->review->name);
		$form->addCheckbox('overwriteCompany', 'Přepsat firmu:')
			->setDefaultValue($this->review->company != null);
		$form->addText('company', 'Firma:')
			->setDefaultValue($this->review->company);
		$form->addTextArea('review', 'Ohlas:')
			->setDefaultValue($this->review->review);
		$form->addText('href', 'Odkaz:')
			->setDefaultValue($this->review->href);
		$form->addCheckbox('hidden', 'Skrýt:')
			->setDefaultValue($this->review->hidden);
		$form->addSubmit('save', 'Uložit');
		$form->onSuccess[] = new \Nette\Callback($this, 'submittedReview');

		return $form;
	}


	public function submittedReview($form)
	{
		$values = $form->getValues();

		$this->trainings->addUpdateReview(
			$this->applicationId,
			$values->overwriteName ? $values->name : null,
			$values->overwriteCompany ? $values->company : null,
			$values->review,
			$values->href,
			$values->hidden
		);

		$this->redirect('termin', $this->review->dateId);
	}


	protected function createComponentApplication($formName)
	{
		$rules = $this->trainingApplications->getDataRules();

		$form = new Form($this, $formName);
		$form->addText('name', 'Jméno:')
			->setDefaultValue($this->application->name)
			->setRequired('Zadejte prosím jméno')
			->addRule(Form::MIN_LENGTH, 'Minimální délka jména je %d znaky', $rules['name'][Form::MIN_LENGTH])
			->addRule(Form::MAX_LENGTH, 'Maximální délka jména je %d znaků', $rules['name'][Form::MAX_LENGTH]);
		$form->addText('email', 'E-mail:')
			->setDefaultValue($this->application->email)
			->setRequired('Zadejte prosím e-mailovou adresu')
			->addRule(Form::EMAIL, 'Zadejte platnou e-mailovou adresu')
			->addRule(Form::MAX_LENGTH, 'Maximální délka e-mailu je %d znaků', $rules['email'][Form::MAX_LENGTH]);
		$form->addText('company', 'Společnost:')
			->setDefaultValue($this->application->company)
			->addCondition(Form::FILLED)
			->addRule(Form::MIN_LENGTH, 'Minimální délka společnosti je %d znaky', $rules['company'][Form::MIN_LENGTH])
			->addRule(Form::MAX_LENGTH, 'Maximální délka společnosti je %d znaků', $rules['company'][Form::MAX_LENGTH]);
		$form->addText('street', 'Ulice:')
			->setDefaultValue($this->application->street)
			->addCondition(Form::FILLED)
			->addRule(Form::MIN_LENGTH, 'Minimální délka ulice je %d znaky', $rules['street'][Form::MIN_LENGTH])
			->addRule(Form::MAX_LENGTH, 'Maximální délka ulice je %d znaků', $rules['street'][Form::MAX_LENGTH]);
		$form->addText('city', 'Město:')
			->setDefaultValue($this->application->city)
			->addCondition(Form::FILLED)
			->addRule(Form::MIN_LENGTH, 'Minimální délka města je %d znaky', $rules['city'][Form::MIN_LENGTH])
			->addRule(Form::MAX_LENGTH, 'Maximální délka města je %d znaků', $rules['city'][Form::MAX_LENGTH]);
		$form->addText('zip', 'PSČ:')
			->setDefaultValue($this->application->zip)
			->addCondition(Form::FILLED)
			->addRule(Form::PATTERN, 'PSČ musí mít 5 číslic', $rules['zip'][Form::PATTERN])
			->addRule(Form::MAX_LENGTH, 'Maximální délka PSČ je %d znaků', $rules['zip'][Form::MAX_LENGTH]);
		$form->addText('companyId', 'IČ:')
			->setDefaultValue($this->application->companyId)
			->addCondition(Form::FILLED)
			->addRule(Form::MIN_LENGTH, 'Minimální délka IČ je %d znaky', $rules['companyId'][Form::MIN_LENGTH])
			->addRule(Form::MAX_LENGTH, 'Maximální délka IČ je %d znaků', $rules['companyId'][Form::MAX_LENGTH]);
		$form->addText('companyTaxId', 'DIČ:')
			->setDefaultValue($this->application->companyTaxId)
			->addCondition(Form::FILLED)
			->addRule(Form::MIN_LENGTH, 'Minimální délka DIČ je %d znaky', $rules['companyTaxId'][Form::MIN_LENGTH])
			->addRule(Form::MAX_LENGTH, 'Maximální délka DIČ je %d znaků', $rules['companyTaxId'][Form::MAX_LENGTH]);
		$form->addText('note', 'Poznámka:')
			->setDefaultValue($this->application->note)
			->addCondition(Form::FILLED)
			->addRule(Form::MAX_LENGTH, 'Maximální délka poznámky je %d znaků', $rules['note'][Form::MAX_LENGTH]);
		$form->addText('price', 'Cena:')
			->setType('number')
			->setAttribute('title', 'Po případné slevě')
			->setDefaultValue($this->application->price);
		$form->addText('discount', 'Sleva:')
			->setType('number')
			->setDefaultValue($this->application->discount);
		$form->addText('invoiceId', 'Faktura č.:')
			->setType('number')
			->setDefaultValue($this->application->invoiceId);
		$this->addDate($form, 'paid', 'Zaplaceno:', false, $this->application->paid);

		$form->addSubmit('submit', 'Uložit');
		$form->onSuccess[] = new \Nette\Callback($this, 'submittedApplication');

		return $form;
	}


	public function submittedApplication($form)
	{
		$values = $form->getValues();

		$this->trainingApplications->updateApplicationData(
			$this->applicationId,
			$values->name,
			$values->email,
			$values->company,
			$values->street,
			$values->city,
			$values->zip,
			$values->companyId,
			$values->companyTaxId,
			$values->note,
			$values->price,
			$values->discount,
			$values->invoiceId,
			$values->paid
		);
		$this->redirect('termin', $this->dateId);
	}


	protected function createComponentFile($formName)
	{
		$form = new Form($this, $formName);
		$form->addUpload('file', 'Soubor:');
		$form->addSubmit('submit', 'Přidat');
		$form->onSuccess[] = new \Nette\Callback($this, 'submittedFile');
		return $form;
	}


	public function submittedFile($form)
	{
		$values = $form->getValues();
		$name = $this->trainingApplications->addFile($this->training, $values->file, $this->applicationIdsAttended);

		$this->flashMessage(
			Html::el()->add('Soubor ')
				->add(Html::el('code')->setText($name))
				->add(' byl přidán')
		);

		$this->redirect($this->getAction(), $this->redirectParam);
	}


}
