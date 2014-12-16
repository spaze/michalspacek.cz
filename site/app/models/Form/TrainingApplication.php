<?php
namespace MichalSpacekCz\Form;

/**
 * Training application form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingApplication extends \Nette\Application\UI\Form
{

	/** @var \Bare\Next\Templating\Helpers */
	protected $bareHelpers;

	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 * @param array $dates
	 * @param \Bare\Next\Templating\Helpers $bareHelpers
	 */
	public function __construct(
		\Nette\ComponentModel\IContainer $parent,
		$name,
		array $dates,
		\Bare\Next\Templating\Helpers $bareHelpers
	)
	{
		parent::__construct($parent, $name);

		$this->bareHelpers = $bareHelpers;

		$inputDates = array();
		foreach ($dates as $date) {
			$format = ($date->tentative ? '%B %Y' : 'j. n. Y');
			$start = $this->bareHelpers->localDate($date->start, 'cs', $format);
			$inputDates[$date->dateId] = "{$start} {$date->venueCity}" . ($date->tentative ? ' (předběžný termín)' : '');
		}

		$label = 'Termín školení:';
		// trainingId is actually dateId, oh well
		if (count($dates) > 1) {
			$this->addSelect('trainingId', $label, $inputDates)
				->setRequired('Vyberte prosím termín a místo školení')
				->setPrompt('- vyberte termín a místo -');
		} else {
			$field = new \Bare\Next\Forms\Controls\HiddenFieldWithLabel($label, $date->dateId, $inputDates[$date->dateId]);
			$this->addComponent($field, 'trainingId');
		}

		$this->addAttendee();
		$this->addCompany();
		$this->addNote();

		$this->addSubmit('signUp', 'Odeslat');
	}


	protected function addAttendee()
	{
		$this->addGroup('Účastník');
		$this->addText('name', 'Jméno a příjmení:')
			->setRequired('Zadejte prosím jméno a příjmení')
			->addRule(self::MIN_LENGTH, 'Minimální délka jména a příjmení je %d znaky', 3)
			->addRule(self::MAX_LENGTH, 'Maximální délka jména a příjmení je %d znaků', 200);
		$this->addText('email', 'E-mail:')
			->setRequired('Zadejte prosím e-mailovou adresu')
			->addRule(self::EMAIL, 'Zadejte platnou e-mailovou adresu')
			->addRule(self::MAX_LENGTH, 'Maximální délka e-mailu je %d znaků', 200);
		$this->setCurrentGroup(null);
	}


	protected function addCompany()
	{
		$this->addGroup('Fakturační údaje');
		$this->addText('company', 'Obchodní jméno:')
			->addCondition(self::FILLED)
			->addRule(self::MIN_LENGTH, 'Minimální délka obchodního jména je %d znaky', 3)
			->addRule(self::MAX_LENGTH, 'Maximální délka obchodního jména je %d znaků', 200);
		$this->addText('street', 'Ulice a číslo:')
			->addCondition(self::FILLED)
			->addRule(self::MIN_LENGTH, 'Minimální délka ulice a čísla je %d znaky', 3)
			->addRule(self::MAX_LENGTH, 'Maximální délka ulice a čísla je %d znaků', 200);
		$this->addText('city', 'Město:')
			->addCondition(self::FILLED)
			->addRule(self::MIN_LENGTH, 'Minimální délka města je %d znaky', 2)
			->addRule(self::MAX_LENGTH, 'Maximální délka města je %d znaků', 200);
		$this->addText('zip', 'PSČ:')
			->addCondition(self::FILLED)
			->addRule(self::PATTERN, 'PSČ musí mít 5 číslic', '([0-9]\s*){5}')
			->addRule(self::MAX_LENGTH, 'Maximální délka PSČ je %d znaků', 200);
		$this->addText('companyId', 'IČ:')
			->addCondition(self::FILLED)
			->addRule(self::MIN_LENGTH, 'Minimální délka IČ je %d znaky', 6)
			->addRule(self::MAX_LENGTH, 'Maximální délka IČ je %d znaků', 200);
		$this->addText('companyTaxId', 'DIČ:')
			->addCondition(self::FILLED)
			->addRule(self::MIN_LENGTH, 'Minimální délka DIČ je %d znaky', 6)
			->addRule(self::MAX_LENGTH, 'Maximální délka DIČ je %d znaků', 200);
		$this->setCurrentGroup(null);
	}


	protected function addNote()
	{
		$this->addText('note', 'Poznámka:')
			->addCondition(self::FILLED)
			->addRule(self::MAX_LENGTH, 'Maximální délka poznámky je %d znaků', 2000);
	}


	/**
	 * @param \Nette\Http\SessionSection $application
	 */
	public function setApplicationFromSession(\Nette\Http\SessionSection $application)
	{
		$values = array(
			'name' => $application->name,
			'email' => $application->email,
			'company' => $application->company,
			'street' => $application->street,
			'city' => $application->city,
			'zip' => $application->zip,
			'companyId' => $application->companyId,
			'companyTaxId' => $application->companyTaxId,
			'note' => $application->note,
		);
		$this->setDefaults($values);
		return $this;
	}

}
