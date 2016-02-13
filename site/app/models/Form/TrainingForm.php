<?php
namespace MichalSpacekCz\Form;

/**
 * Abstract training form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
abstract class TrainingForm extends Form
{

	/** @var \Nette\Localization\ITranslator */
	protected $translator;


	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(
		\Nette\ComponentModel\IContainer $parent,
		$name,
		\Nette\Localization\ITranslator $translator
	)
	{
		parent::__construct($parent, $name);
		$this->translator = $translator;
	}


	/**
	 * Adds paid date input control to the form.
	 * @param string control name
	 * @param string label
	 * @param boolean required
	 * @param integer width of the control (deprecated)
	 * @param integer maximum number of characters the user may enter
	 * @return \Nette\Forms\Controls\TextInput
	 */
	protected function addPaidDate($name, $label = null, $required = false, $cols = null, $maxLength = null)
	{
		return $this->addDate(
			$name,
			$label,
			$required,
			$cols,
			$maxLength,
			'YYYY-MM-DD nebo YYYY-MM-DD HH:MM:SS nebo DD.MM.YYYY nebo NOW',
			'((\d{4}-\d{1,2}-\d{1,2})( \d{1,2}:\d{2}:\d{2})?)|(\d{1,2}\.\d{1,2}\.\d{4})|[Nn][Oo][Ww]'
		);
	}


	/**
	 * Adds status date input control to the form.
	 * @param string control name
	 * @param string label
	 * @param boolean required
	 * @param integer width of the control (deprecated)
	 * @param integer maximum number of characters the user may enter
	 * @return \Nette\Forms\Controls\TextInput
	 */
	protected function addStatusDate($name, $label = null, $required = false, $cols = null, $maxLength = null)
	{
		return $this->addDate(
			$name,
			$label,
			$required,
			$cols,
			$maxLength,
			'YYYY-MM-DD HH:MM:SS nebo DD.MM.YYYY HH:MM:SS nebo NOW',
			'(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2}:\d{2})|[Nn][Oo][Ww]'
		);
	}


	/**
	 * Add attendee inputs.
	 *
	 * @param \Nette\Forms\Container $container
	 */
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


	/**
	 * Add attributes inputs.
	 *
	 * @param \Nette\Forms\Container $container
	 */
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


	/**
	 * Add company inputs.
	 *
	 * @param \Nette\Forms\Container $container
	 */
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


	/**
	 * Add note input.
	 *
	 * @param \Nette\Forms\Container $container
	 */
	protected function addNote(\Nette\Forms\Container $container)
	{
		$container->addText('note', 'Poznámka:')
			->addCondition(self::FILLED)
			->addRule(self::MAX_LENGTH, 'Maximální délka poznámky je %d znaků', 2000);
	}


	/**
	 * Add country input.
	 *
	 * @param \Nette\Forms\Container $container
	 */
	protected function addCountry(\Nette\Forms\Container $container)
	{
		$container->addSelect('country', 'Země:', ['cz' => 'Česká republika', 'sk' => 'Slovensko'])
			->setRequired('Vyberte prosím zemi');
	}


}
