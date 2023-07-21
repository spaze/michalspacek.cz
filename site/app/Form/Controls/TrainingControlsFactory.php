<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use Contributte\Translation\Translator;
use MichalSpacekCz\EasterEgg\WinterIsComing;
use MichalSpacekCz\Training\Applications\TrainingApplicationSources;
use Nette\Forms\Container;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;

class TrainingControlsFactory
{

	public function __construct(
		private readonly TrainingApplicationSources $trainingApplicationSources,
		private readonly WinterIsComing $winterIsComing,
		private readonly Translator $translator,
	) {
	}


	public function addAttendee(Container $container): void
	{
		$container->addText('name', 'Jméno a příjmení:')
			->setRequired('Zadejte prosím jméno a příjmení')
			->addRule(Form::MIN_LENGTH, 'Minimální délka jména a příjmení je %d znaky', 3)
			->addRule(Form::MAX_LENGTH, 'Maximální délka jména a příjmení je %d znaků', 200);
		$container->addText('email', 'E-mail:')
			->setRequired('Zadejte prosím e-mailovou adresu')
			->addRule(Form::EMAIL, 'Zadejte platnou e-mailovou adresu')
			->addRule(Form::MAX_LENGTH, 'Maximální délka e-mailu je %d znaků', 200)
			->addRule($this->winterIsComing->ruleEmail(), 'Winter is actually not coming');
	}


	public function addCompany(Container $container): void
	{
		$container->addText('companyId', 'IČO:')
			->setRequired(false)
			->addRule(Form::MIN_LENGTH, 'Minimální délka IČO je %d znaky', 6)
			->addRule(Form::MAX_LENGTH, 'Maximální délka IČO je %d znaků', 200);
		$container->addText('companyTaxId', 'DIČ:')
			->setRequired(false)
			->addRule(Form::MIN_LENGTH, 'Minimální délka DIČ je %d znaky', 6)
			->addRule(Form::MAX_LENGTH, 'Maximální délka DIČ je %d znaků', 200)
			->getLabelPrototype()
			->addAttributes([
				'data-cz' => $this->translator->translate('messages.label.taxid.cz') . ':',
				'data-sk' => $this->translator->translate('messages.label.taxid.sk') . ':',
			]);
		$container->addText('company', 'Obchodní jméno:')
			->setRequired(false)
			->addRule(Form::MIN_LENGTH, 'Minimální délka obchodního jména je %d znaky', 3)
			->addRule(Form::MAX_LENGTH, 'Maximální délka obchodního jména je %d znaků', 200);
		$container->addText('street', 'Ulice a číslo:')
			->setRequired(false)
			->addRule(Form::MIN_LENGTH, 'Minimální délka ulice a čísla je %d znaky', 3)
			->addRule(Form::MAX_LENGTH, 'Maximální délka ulice a čísla je %d znaků', 200)
			->addRule($this->winterIsComing->ruleStreet(), 'Winter is actually not coming');
		$container->addText('city', 'Město:')
			->setRequired(false)
			->addRule(Form::MIN_LENGTH, 'Minimální délka města je %d znaky', 2)
			->addRule(Form::MAX_LENGTH, 'Maximální délka města je %d znaků', 200);
		$container->addText('zip', 'PSČ:')
			->setRequired(false)
			->addRule(Form::PATTERN, 'PSČ musí mít 5 číslic', '([0-9]\s*){5}')
			->addRule(Form::MAX_LENGTH, 'Maximální délka PSČ je %d znaků', 200);
	}


	public function addCountry(Container $container): void
	{
		$container->addSelect('country', 'Země:', ['cz' => 'Česká republika', 'sk' => 'Slovensko'])
			->setRequired('Vyberte prosím zemi');
	}


	public function addNote(Container $container): TextInput
	{
		return $container->addText('note', 'Poznámka:')
			->setRequired(false)
			->addRule(Form::MAX_LENGTH, 'Maximální délka poznámky je %d znaků', 2000);
	}


	public function addSource(Container $container): SelectBox
	{
		return $container->addSelect('source', 'Zdroj:', $this->trainingApplicationSources->getAll())
			->setRequired('Vyberte zdroj');
	}


	public function addDate(TextInput $field, bool $required, string $format, string $pattern): TextInput
	{
		$field
			->setHtmlAttribute('placeholder', $format)
			->setHtmlAttribute('title', "Formát {$format}")
			->setRequired($required ? 'Zadejte datum' : false)
			->addRule(Form::PATTERN, "Datum musí být ve formátu {$format}", $pattern);
		return $field;
	}


	public function addStatusDate(TextInput $field, bool $required): TextInput
	{
		return $this->addDate(
			$field,
			$required,
			'YYYY-MM-DD HH:MM:SS nebo DD.MM.YYYY HH:MM:SS nebo NOW',
			'(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2}:\d{2})|[Nn][Oo][Ww]',
		);
	}


	public function addPaidDate(TextInput $field, bool $required): TextInput
	{
		return $this->addDate(
			$field,
			$required,
			'YYYY-MM-DD nebo YYYY-MM-DD HH:MM:SS nebo DD.MM.YYYY nebo NOW',
			'((\d{4}-\d{1,2}-\d{1,2})( \d{1,2}:\d{2}:\d{2})?)|(\d{1,2}\.\d{1,2}\.\d{4})|[Nn][Oo][Ww]',
		);
	}

}
