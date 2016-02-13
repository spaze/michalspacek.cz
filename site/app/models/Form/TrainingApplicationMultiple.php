<?php
namespace MichalSpacekCz\Form;

/**
 * Training application form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingApplicationMultiple extends TrainingForm
{

	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 * @param integer $count
	 * @param array $sources
	 * @param array $statuses
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, $name, $count, $sources, $statuses, \Nette\Localization\ITranslator $translator)
	{
		parent::__construct($parent, $name, $translator);
		$this->addProtection('Platnost formuláře vypršela, odešlete jej znovu');

		$applicationsContainer = $this->addContainer('applications');
		for ($i = 0; $i < $count; $i++) {
			$dataContainer = $applicationsContainer->addContainer($i);
			$this->addAttendee($dataContainer);
			$this->addAttributes($dataContainer);
			$this->addCompany($dataContainer);
			$this->addNote($dataContainer);
			$dataContainer->getComponent('name')->caption = 'Jméno:';
			$dataContainer->getComponent('company')->caption = 'Společnost:';
			$dataContainer->getComponent('street')->caption = 'Ulice:';
		}

		$this->addCountry($this);
		$this->addStatusDate('date', 'Datum:', true);
		$this->addSelect('status', 'Status:', $statuses)
			->setRequired('Vyberte status')
			->setPrompt('- vyberte status -');
		$this->addSelect('source', 'Zdroj:', $sources)
			->setRequired('Vyberte zdroj')
			->setPrompt('- vyberte zdroj -');

		$this->addSubmit('submit', 'Přidat');
	}


	protected function addAttributes(\Nette\Forms\Container $container)
	{
		$container->addCheckbox('equipment', 'PC');
	}

}
