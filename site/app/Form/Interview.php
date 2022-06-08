<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Nette\ComponentModel\IContainer;
use Nette\Database\Row;
use Nette\Forms\Controls\TextInput;

class Interview extends ProtectedForm
{

	public function __construct(IContainer $parent, string $name, private readonly TrainingControlsFactory $trainingControlsFactory)
	{
		parent::__construct($parent, $name);

		$this->addText('action', 'Akce:')
			->setRequired('Zadejte prosím akci')
			->addRule(self::MAX_LENGTH, 'Maximální délka akce je %d znaků', 200);
		$this->addText('title', 'Název:')
			->setRequired('Zadejte prosím název')
			->addRule(self::MAX_LENGTH, 'Maximální délka názvu je %d znaků', 200);
		$this->addTextArea('description', 'Popis:')
			->setRequired(false);
		$this->addInterviewDate('date', 'Datum:', true);
		$this->addText('href', 'Odkaz na rozhovor:')
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


	/**
	 * @param Row<mixed> $interview
	 * @return $this
	 */
	public function setInterview(Row $interview): self
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


	private function addInterviewDate(string $name, string $label, bool $required = false): TextInput
	{
		return $this->trainingControlsFactory->addDate(
			$this,
			$name,
			$label,
			$required,
			'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM',
			'(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2})',
		);
	}

}
