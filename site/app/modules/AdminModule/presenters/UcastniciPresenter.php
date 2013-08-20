<?php
namespace AdminModule;

use \Nette\Application\UI\Form;

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

	/** @var \Nette\Database\Row */
	private $review;

	private $applicationId;

	private $dateId;


	private function addDate($form, $name, $label)
	{
		$form->addText($name, $label)
			->setAttribute('placeholder', 'YYYY-MM-DD HH:MM:SS nebo NOW')
			->setAttribute('title', 'Formát  YYYY-MM-DD HH:MM:SS nebo NOW')
			->setRequired('Zadejte datum')
			->addRule(Form::PATTERN, 'Datum musí být ve formátu YYYY-MM-DD HH:MM:SS nebo NOW', '(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})|[Nn][Oo][Ww]');
	}


	public function actionTermin($param)
	{
		$this->dateId = $param;
		$training = $this->trainings->getByDate($this->dateId);
		$this->applications = $this->trainingApplications->getByDate($this->dateId);
		$attendedStatuses = $this->trainingApplications->getAttendedStatuses();
		foreach ($this->applications as $application) {
			$application->attended = in_array($application->status, $attendedStatuses);
			$application->childrenStatuses = $this->trainingApplications->getChildrenStatuses($application->status);
		}

		$this->template->pageTitle     = 'Účastníci zájezdu';
		$this->template->trainingStart = $training->start;
		$this->template->trainingName  = $training->name;
		$this->template->applications  = $this->applications;
	}


	public function actionSoubory($param)
	{
		$application = $this->trainingApplications->getApplicationById($param);
		if (!in_array($application->status, $this->trainingApplications->getAttendedStatuses())) {
			$this->redirect('termin', $application->dateId);
		}

		$files = $this->trainingApplications->getFiles($param);
		foreach ($files as $file) {
			$file->exists = file_exists("{$file->dirName}/{$file->fileName}");
		}

		$date = $this->trainings->getByDate($application->dateId);

		$this->template->pageTitle = 'Soubory';
		$this->template->files     = $files;
		$this->template->trainingStart = $date->start;
		$this->template->trainingName  = $date->name;
		$this->template->trainingCity  = $date->venueCity;
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


	public function renderDefault()
	{
		$this->template->pageTitle = 'Import';
		$this->template->trainings = $this->trainings->getAllTrainings();
		$this->template->now = new \DateTime();
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
		$this->redirect($this->getAction(), $this->dateId);
	}


	protected function createComponentApplications($formName)
	{
		$helpers = new \Bare\Next\Templating\Helpers();

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
				->addRule(Form::MIN_LENGTH, 'Minimální délka jména je %d znaky', 3)
				->addRule(Form::MAX_LENGTH, 'Maximální délka jména je %d znaků', 200);
			$dataContainer->addText('email', 'E-mail')
				->setRequired('Zadejte prosím e-mailovou adresu')
				->addRule(Form::EMAIL, 'Zadejte platnou e-mailovou adresu')
				->addRule(Form::MAX_LENGTH, 'Maximální délka e-mailu je %d znaků', 200);
			$dataContainer->addText('company', 'Společnost')
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka společnosti je %d znaky', 3)
				->addRule(Form::MAX_LENGTH, 'Maximální délka společnosti je %d znaků', 200);
			$dataContainer->addText('street', 'Ulice')
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka ulice je %d znaky', 3)
				->addRule(Form::MAX_LENGTH, 'Maximální délka ulice je %d znaků', 200);
			$dataContainer->addText('city', 'Město')
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka města je %d znaky', 2)
				->addRule(Form::MAX_LENGTH, 'Maximální délka města je %d znaků', 200);
			$dataContainer->addText('zip', 'PSČ')
				->addCondition(Form::FILLED)
				->addRule(Form::PATTERN, 'PSČ musí mít 5 číslic', '([0-9]\s*){5}')
				->addRule(Form::MAX_LENGTH, 'Maximální délka PSČ je %d znaků', 200);
			$dataContainer->addText('companyId', 'IČ')
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka IČ je %d znaky', 6)
				->addRule(Form::MAX_LENGTH, 'Maximální délka IČ je %d znaků', 200);
			$dataContainer->addText('companyTaxId', 'DIČ')
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka DIČ je %d znaky', 6)
				->addRule(Form::MAX_LENGTH, 'Maximální délka DIČ je %d znaků', 200);
			$dataContainer->addText('note', 'Poznámka')
				->addCondition(Form::FILLED)
				->addRule(Form::MAX_LENGTH, 'Maximální délka poznámky je %d znaků', 2000);
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


}
