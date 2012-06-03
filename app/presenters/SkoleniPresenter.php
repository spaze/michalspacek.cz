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
	 * Currently processed training.
	 *
	 * @var integer
	 */
	private $trainingId;

	public function renderDefault()
	{
		$this->template->pageTitle = 'Školení';
		$this->template->trainings = $this->trainings;
	}

	public function actionUvodDoPhp()
	{
		$this->trainingId = self::TRAINING_PHP;
	}

	public function renderUvodDoPhp()
	{
		$this->assignTemplateVariables();
	}

	public function actionProgramovaniVPhp5()
	{
		$this->trainingId = self::TRAINING_PHP5;
	}

	public function renderProgramovaniVPhp5()
	{
		$this->assignTemplateVariables();
	}

	public function actionBezpecnostPhpAplikaci()
	{
		$this->trainingId = self::TRAINING_SECURITY;
	}

	public function renderBezpecnostPhpAplikaci()
	{
		$this->assignTemplateVariables();
	}

	public function actionVykonnostWebovychAplikaci()
	{
		$this->trainingId = self::TRAINING_PERFORMANCE;
	}

	public function renderVykonnostWebovychAplikaci()
	{
		$this->assignTemplateVariables();
	}

	private function assignTemplateVariables()
	{
		$this->template->trainingId = $this->trainingId;
		$this->template->pageTitle = 'Školení ' . $this->trainings[$this->trainingId]['name'];
		$this->template->date = $this->trainings[$this->trainingId]['date'];
		$this->template->tentative = $this->trainings[$this->trainingId]['tentative'];
		$this->template->originalUrl = $this->trainings[$this->trainingId]['originalUrl'];
		$this->template->placeName = $this->trainings[$this->trainingId]['placeName'];
		$this->template->placeUrl = $this->trainings[$this->trainingId]['placeUrl'];
		$this->template->placeAddress = $this->trainings[$this->trainingId]['placeAddress'];
		$this->template->pastTrainings = $this->trainings[$this->trainingId]['pastTrainings'];
	}

	protected function createComponentApplication($name)
	{
		$form = new Form($this, $name);
		$form->setAction($form->getAction() . '#prihlaska');
		$form->addHidden('trainingId');
		$form->addHidden('date');
		$form->addGroup('Účastník');
		$form->addText('name', 'Jméno a příjmení:')
			->setRequired('Zadejte prosím jméno a příjmení')
			->addRule(Form::MIN_LENGTH, 'Minimální délka jména a příjmení je tři znaky', 3)
			->addRule(Form::MAX_LENGTH, 'Maximální délka jména a příjmení je sto znaků', 100);
		$form->addText('email', 'E-mail:')
			->setRequired('Zadejte prosím e-mailovou adresu')
			->addRule(Form::EMAIL, 'Zadejte platnou e-mailovou adresu')
			->addRule(Form::MAX_LENGTH, 'Maximální délka e-mailu je sto znaků', 100);

		if (isset($this->trainings[$this->trainingId]['date'])) {
			$form->addGroup('Fakturační údaje');
			$form->addText('company', 'Obchodní jméno:')
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka obchodního jména je tři znaky', 3)
				->addRule(Form::MAX_LENGTH, 'Maximální délka obchodního jména je sto znaků', 100);
			$form->addText('street', 'Ulice a číslo:')
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka ulice a čísla je tři znaky', 3)
				->addRule(Form::MAX_LENGTH, 'Maximální délka ulice a čísla je sto znaků', 100);
			$form->addText('city', 'Město:')
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka města je dva znaky', 2)
				->addRule(Form::MAX_LENGTH, 'Maximální délka města je sto znaků', 100);
			$form->addText('zip', 'PSČ:')
				->addCondition(Form::FILLED)
				->addRule(Form::PATTERN, 'PSČ musí mít 5 číslic', '([0-9]\s*){5}')
				->addRule(Form::MAX_LENGTH, 'Maximální délka PSČ je sto znaků', 100);
			$form->addText('companyId', 'IČ:')
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka IČ je tři znaky', 3)
				->addRule(Form::MAX_LENGTH, 'Maximální délka IČ je sto znaků', 100);
			$form->addText('companyTaxId', 'DIČ:')
				->addCondition(Form::FILLED)
				->addRule(Form::MIN_LENGTH, 'Minimální délka DIČ je tři znaky', 3)
				->addRule(Form::MAX_LENGTH, 'Maximální délka DIČ je sto znaků', 100);
		}

		$form->setCurrentGroup(null);
		$form->addText('note', 'Poznámka:')
			->addCondition(Form::FILLED)
			->addRule(Form::MAX_LENGTH, 'Maximální délka poznámky je tisíc znaků', 1000);
		$form->addSubmit('signUp', isset($this->template->date) ? 'Registrovat se' : 'Odeslat');
		$form->onSuccess[] = callback($this, 'submittedApplication');

		return $form;
	}

	public function submittedApplication($form)
	{
		$values = $form->getValues();
		try {
			$database = $this->getContext()->nette->database->default;
			$datetime = new DateTime();
			if (empty($values['date'])) {
				$database->exec('INSERT INTO training_invitations', array(
					'key_training' => $values['trainingId'],
					'name' => $values['name'],
					'email' => $values['email'],
					'note' => $values['note'],
					'created' => $datetime,
					'created_timezone' => $datetime->getTimezone()->getName(),
				));
				$this->flashMessage('Díky, přihláška odeslána! Dám vám vědět, jakmile budu znát přesný termín.');
			} else {
				$database->exec('INSERT INTO training_applications', array(
					'key_training' => $values['trainingId'],
					'date' => $values['date'],
					'name' => $values['name'],
					'email' => $values['email'],
					'company' => $values['company'],
					'street' => $values['street'],
					'city' => $values['city'],
					'zip' => $values['zip'],
					'company_id' => $values['companyId'],
					'company_tax_id' => $values['companyTaxId'],
					'note' => $values['note'],
					'created' => $datetime,
					'created_timezone' => $datetime->getTimezone()->getName(),
				));
				$this->flashMessage('Díky, přihláška odeslána! Potvrzení společně s fakturou vám přijde do druhého pracovního dne.');
			}
			$action = (isset($this->trainings[$values['trainingId']]) ? $this->trainings[$values['trainingId']]['action'] : '');
			$this->redirect($this->getName() . ':' . $action);
		} catch (PDOException $e) {
 			Debugger::log($e, Debugger::ERROR);
			$this->flashMessage('Ups, něco se rozbilo a přihlášku se nepodařilo odeslat, zkuste to za chvíli znovu.', 'error');
		}
	}
}
