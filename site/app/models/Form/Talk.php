<?php
namespace MichalSpacekCz\Form;

/**
 * Talk form.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Talk extends ProtectedForm
{

	use Controls\Date;

	/** @var \MichalSpacekCz\Talks */
	protected $talks;


	public function __construct(
		\Nette\ComponentModel\IContainer $parent,
		$name,
		$talkAction,
		\MichalSpacekCz\Talks $talks
	)
	{
		parent::__construct($parent, $name);
		$this->talks = $talks;

		$talks = array();
		foreach ($this->talks->getAll() as $talk) {
			if ($talkAction !== $talk->action) {
				$title = \Latte\Runtime\Filters::truncate($talk->titleTexy, 40);
				$event = \Latte\Runtime\Filters::truncate($talk->event, 30);
				$talks[$talk->action] = sprintf('%s (%s, %s)', $title, $talk->date->format('j. n. Y'), $event);
			}
		}

		$this->addText('action', 'Akce:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka akce je %d znaků', 200);
		$this->addText('title', 'Název:')
			->setRequired('Zadejte prosím název')
			->addRule(self::MAX_LENGTH, 'Maximální délka názvu je %d znaků', 200);
		$this->addTextArea('description', 'Popis:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka popisu je %d znaků', 65535);
		$this->addTalkDate('date', 'Datum:', true);
		$this->addText('href', 'Odkaz na přednášku:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka odkazu na přednášku je %d znaků', 200);
		$this->addText('duration', 'Délka:')
			->setType('number');
		$this->addSelect('origSlides', 'Odkázat na slajdy z:', $talks)
			->setPrompt('Vyberte prosím přednášku, na kterou se odkáže');
		$this->addText('slidesHref', 'Odkaz na slajdy:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka odkazu na slajdy je %d znaků', 200);
		$this->addText('slidesEmbed', 'Embed odkaz na slajdy:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka embed odkazu na slajdy je %d znaků', 200);
		$this->addText('videoHref', 'Odkaz na video:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka odkazu na video je %d znaků', 200);
		$this->addText('videoEmbed', 'Embed odkaz na video:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka embed odkazu na video je %d znaků', 200);
		$this->addText('event', 'Událost:')
			->setRequired('Zadejte prosím událost')
			->addRule(self::MAX_LENGTH, 'Maximální délka události je %d znaků', 200);
		$this->addText('eventHref', 'Odkaz na událost:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka odkazu na událost je %d znaků', 200);
		$this->addText('ogImage', 'Odkaz na obrázek:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka odkazu na obrázek je %d znaků', 200);
		$this->addTextArea('transcript', 'Přepis:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka přepisu je %d znaků', 65535);
		$this->addTextArea('favorite', 'Popis pro oblíbené:')
			->setRequired(false)
			->addRule(self::MAX_LENGTH, 'Maximální délka popisu pro oblíbené je %d znaků', 65535);
		$this->addSelect('supersededBy', 'Nahrazeno přednáškou:', $talks)
			->setPrompt('Vyberte prosím přednášku, kterou se tato nahradí');

		$this->addSubmit('submit', 'Přidat');
	}


	public function setTalk(\Nette\Database\Row $talk)
	{
		$values = array(
			'action' => $talk->action,
			'title' => $talk->titleTexy,
			'description' => $talk->descriptionTexy,
			'date' => $talk->date->format('Y-m-d H:i'),
			'href' => $talk->href,
			'duration' => $talk->duration,
			'origSlides' => $talk->origAction,
			'slidesHref' => $talk->slidesHref,
			'slidesEmbed' => $talk->slidesEmbed,
			'videoHref' => $talk->videoHref,
			'videoEmbed' => $talk->videoEmbed,
			'event' => $talk->eventTexy,
			'eventHref' => $talk->eventHref,
			'ogImage' => $talk->ogImage,
			'transcript' => $talk->transcriptTexy,
			'favorite' => $talk->favorite,
			'supersededBy' => $talk->supersededByAction,
		);
		$this->setDefaults($values);
		$this->getComponent('submit')->caption = 'Upravit';

		return $this;
	}


	/**
	 * Adds talk date input control to the form.
	 * @param string control name
	 * @param string label
	 * @param boolean required
	 * @param integer width of the control (deprecated)
	 * @param integer maximum number of characters the user may enter
	 * @return \Nette\Forms\Controls\TextInput
	 */
	protected function addTalkDate($name, $label = null, $required = false, $cols = null, $maxLength = null)
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
