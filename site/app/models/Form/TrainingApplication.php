<?php
namespace MichalSpacekCz\Form;

/**
 * Training application form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingApplication extends TrainingForm
{

	/** @var \Nette\Localization\ITranslator */
	protected $translator;

	/** @var \Netxten\Templating\Helpers */
	protected $netxtenHelpers;

	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 * @param array $dates
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \Netxten\Templating\Helpers $netxtenHelpers
	 */
	public function __construct(
		\Nette\ComponentModel\IContainer $parent,
		$name,
		array $dates,
		\Nette\Localization\ITranslator $translator,
		\Netxten\Templating\Helpers $netxtenHelpers
	)
	{
		parent::__construct($parent, $name);
		$this->addProtection('Platnost formuláře vypršela, odešlete jej znovu');

		$this->translator = $translator;
		$this->netxtenHelpers = $netxtenHelpers;

		$inputDates = array();
		foreach ($dates as $date) {
			$format = ($date->tentative ? '%B %Y' : 'j. n. Y');
			$start = $this->netxtenHelpers->localDate($date->start, 'cs', $format);
			$inputDates[$date->dateId] = "{$start} {$date->venueCity}" . ($date->tentative ? ' (' . $this->translator->translate('messages.label.tentativedate') . ')' : '');
		}

		$label = 'Termín školení:';
		// trainingId is actually dateId, oh well
		if (count($dates) > 1) {
			$this->addSelect('trainingId', $label, $inputDates)
				->setRequired('Vyberte prosím termín a místo školení')
				->setPrompt('- vyberte termín a místo -');
		} else {
			$field = new \Netxten\Forms\Controls\HiddenFieldWithLabel($label, $date->dateId, $inputDates[$date->dateId]);
			$this->addComponent($field, 'trainingId');
		}

		$this->addAttendee($this);
		$this->addAttributes($this);
		$this->addCompany($this);
		$this->addNote($this);
		$this->addCountry($this);

		$this->addSubmit('signUp', 'Odeslat');
	}


	protected function addAttendee(\Nette\Forms\Container $container)
	{
		$container->addText('name', 'Jméno a příjmení:')
			->setRequired('Zadejte prosím jméno a příjmení')
			->addRule(self::MIN_LENGTH, 'Minimální délka jména a příjmení je %d znaky', 3)
			->addRule(self::MAX_LENGTH, 'Maximální délka jména a příjmení je %d znaků', 200);
		$container->addText('email', 'E-mail:')
			->setRequired('Zadejte prosím e-mailovou adresu')
			->addRule(self::EMAIL, 'Zadejte platnou e-mailovou adresu')
			->addRule(self::MAX_LENGTH, 'Maximální délka e-mailu je %d znaků', 200);
	}


	protected function addAttributes(\Nette\Forms\Container $container)
	{
		$options = array(
			0 => $this->translator->translate('messages.label.no'),
			1 => $this->translator->translate('messages.label.yes'),
		);
		$container->addRadioList('equipment', 'Přinesete si vlastní počítač?', $options)
			->setRequired('Přinesete si vlastní počítač?')
			->getSeparatorPrototype()->setName('div');
	}


	protected function addCompany(\Nette\Forms\Container $container)
	{
		$container->addText('companyId', 'IČO:')
			->addCondition(self::FILLED)
			->addRule(self::MIN_LENGTH, 'Minimální délka IČO je %d znaky', 6)
			->addRule(self::MAX_LENGTH, 'Maximální délka IČO je %d znaků', 200);
		$container->addText('companyTaxId', 'DIČ:')
			->addCondition(self::FILLED)
			->addRule(self::MIN_LENGTH, 'Minimální délka DIČ je %d znaky', 6)
			->addRule(self::MAX_LENGTH, 'Maximální délka DIČ je %d znaků', 200);
		$container->addText('company', 'Obchodní jméno:')
			->addCondition(self::FILLED)
			->addRule(self::MIN_LENGTH, 'Minimální délka obchodního jména je %d znaky', 3)
			->addRule(self::MAX_LENGTH, 'Maximální délka obchodního jména je %d znaků', 200);
		$container->addText('street', 'Ulice a číslo:')
			->addCondition(self::FILLED)
			->addRule(self::MIN_LENGTH, 'Minimální délka ulice a čísla je %d znaky', 3)
			->addRule(self::MAX_LENGTH, 'Maximální délka ulice a čísla je %d znaků', 200);
		$container->addText('city', 'Město:')
			->addCondition(self::FILLED)
			->addRule(self::MIN_LENGTH, 'Minimální délka města je %d znaky', 2)
			->addRule(self::MAX_LENGTH, 'Maximální délka města je %d znaků', 200);
		$container->addText('zip', 'PSČ:')
			->addCondition(self::FILLED)
			->addRule(self::PATTERN, 'PSČ musí mít 5 číslic', '([0-9]\s*){5}')
			->addRule(self::MAX_LENGTH, 'Maximální délka PSČ je %d znaků', 200);
	}


	protected function addNote(\Nette\Forms\Container $container)
	{
		$container->addText('note', 'Poznámka:')
			->addCondition(self::FILLED)
			->addRule(self::MAX_LENGTH, 'Maximální délka poznámky je %d znaků', 2000);
	}


	protected function addCountry(\Nette\Forms\Container $container)
	{
		$container->addSelect('country', 'Země:', ['cz' => 'Česká republika', 'sk' => 'Slovensko'])
			->setRequired('Vyberte prosím zemi');
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
			'country' => $application->country,
			'companyId' => $application->companyId,
			'companyTaxId' => $application->companyTaxId,
			'note' => $application->note,
			'equipment' => $application->equipment,
		);
		$this->setDefaults($values);
		return $this;
	}

}
