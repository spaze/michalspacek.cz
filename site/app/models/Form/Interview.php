<?php
namespace MichalSpacekCz\Form;

/**
 * Interview form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Interview extends \Nette\Application\UI\Form
{

	use Controls\Date;

	public function __construct(\Nette\ComponentModel\IContainer $parent, $name)
	{
		parent::__construct($parent, $name);
		$this->addProtection('Platnost formuláře vypršela, odešlete jej znovu');

		$this->addText('action', 'Akce:')
			->setRequired('Zadejte prosím akci')
			->addRule(self::MAX_LENGTH, 'Maximální délka akce je %d znaků', 200);
		$this->addText('title', 'Název:')
			->setRequired('Zadejte prosím název')
			->addRule(self::MAX_LENGTH, 'Maximální délka názvu je %d znaků', 200);
		$this->addTextArea('description', 'Popis:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka popisu je %d znaků', 2000);
		$this->addInterviewDate('date', 'Datum:', true);
		$this->addText('href', 'Odkaz na rozhovor:', true)
			->setRequired('Zadejte prosím odkaz na rozhovor')
			->addRule(self::MAX_LENGTH, 'Maximální délka odkazu na rozhovor je %d znaků', 200);
		$this->addText('audioHref', 'Odkaz na audio:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka odkazu na audio je %d znaků', 200);
		$this->addText('audioEmbed', 'Embed odkaz na audio:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka embed odkazu na audio je %d znaků', 200);
		$this->addText('videoHref', 'Odkaz na video:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka odkazu na video je %d znaků', 200);
		$this->addText('videoEmbed', 'Embed odkaz na video:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka embed odkazu na video je %d znaků', 200);
		$this->addText('sourceName', 'Název zdroje:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka názvu zdroje je %d znaků', 200);
		$this->addText('sourceHref', 'Odkaz na zdroj:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka odkazu na zdroj je %d znaků', 200);
		$this->addSubmit('submit', 'Přidat');
	}


	public function setInterview(\Nette\Database\Row $interview)
	{
		$values = array(
			'action' => $interview->action,
			'title' => $interview->title,
			'description' => $interview->descriptionTexy,
			'date' => $interview->date->format('Y-m-d H:i'),
			'href' => $interview->href,
			'audioHref' => $interview->audioHref,
			'audioEmbed' => $interview->audioEmbed,
			'videoHref' => $interview->videoHref,
			'videoEmbed' => $interview->videoEmbed,
			'sourceName' => $interview->sourceName,
			'sourceHref' => $interview->sourceHref,
		);
		$this->setDefaults($values);
		$this->getComponent('submit')->caption = 'Upravit';

		return $this;
	}


	/**
	 * Adds interview date input control to the form.
	 * @param string control name
	 * @param string label
	 * @param boolean required
	 * @param integer width of the control (deprecated)
	 * @param integer maximum number of characters the user may enter
	 * @return \Nette\Forms\Controls\TextInput
	 */
	private function addInterviewDate($name, $label = null, $required = false, $cols = null, $maxLength = null)
	{
		return $this->addDate(
			$name,
			$label,
			$required,
			$cols,
			$maxLength,
			'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM',
			'(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2})'
		);
	}

}
