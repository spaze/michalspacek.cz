<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

class TalkSlides extends ProtectedForm
{

	/** @var \MichalSpacekCz\Talks */
	protected $talks;


	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 * @param \Nette\Database\Row[] $slides
	 * @param integer $newCount
	 * @param \MichalSpacekCz\Talks $talks
	 */
	public function __construct(\Nette\ComponentModel\IContainer $parent, string $name, array $slides, int $newCount, \MichalSpacekCz\Talks $talks)
	{
		parent::__construct($parent, $name);
		$this->talks = $talks;

		$slidesContainer = $this->addContainer('slides');
		foreach ($slides as $slide) {
			$slideIdContainer = $slidesContainer->addContainer($slide->slideId);
			$this->addSlideFields($slideIdContainer);
			$values = array(
				'alias' => $slide->alias,
				'number' => $slide->number,
				'title' => $slide->title,
				'filename' => $slide->filename,
				'filenameAlternative' => $slide->filenameAlternative,
				'speakerNotes' => $slide->speakerNotesTexy,
			);
			$slideIdContainer->setDefaults($values);
		}

		if (empty($slides) && $newCount === 0) {
			$newCount = 1;
		}
		$newContainer = $this->addContainer('new');
		for ($i = 0; $i < $newCount; $i++) {
			$newIdContainer = $newContainer->addContainer($i);
			$this->addSlideFields($newIdContainer);
		}

		$this->addCheckbox('deleteReplaced', 'Smazat nahrazené soubory?');
		$this->addSubmit('submit', 'Upravit');
	}


	private function addSlideFields(\Nette\Forms\Container $container): void
	{
		$container->addText('alias', 'Alias:')
			->setRequired('Zadejte prosím alias')
			->addRule(self::PATTERN, 'Alias musí být ve formátu [_.,a-z0-9-]+', '[_.,a-z0-9-]+');
		$container->addText('number', 'Slajd:')
			->setHtmlType('number')
			->setDefaultValue(1)
			->setHtmlAttribute('class', 'right slide-nr')
			->setRequired('Zadejte prosím číslo slajdu');
		$container->addText('title', 'Titulek:')
			->setRequired('Zadejte prosím titulek');
		$upload = $container->addUpload('replace', 'Nahradit:')
			->setHtmlAttribute('title', 'Nahradit soubor (*.' . implode(', *.', $this->talks->getSupportedImages()) . ')')
			->setHtmlAttribute('accept', implode(',', array_keys($this->talks->getSupportedImages())));
		$container->addText('filename', 'Soubor:')
			->setHtmlAttribute('class', 'slide-filename')
			->addConditionOn($upload, self::BLANK)
				->setRequired('Zadejte prosím soubor');
		$container->addUpload('replaceAlternative', 'Nahradit:')
			->setHtmlAttribute('title', 'Nahradit alternativní soubor (*.' . implode(', *.', $this->talks->getSupportedAlternativeImages()) . ')')
			->setHtmlAttribute('accept', implode(',', array_keys($this->talks->getSupportedAlternativeImages())));
		$container->addText('filenameAlternative', 'Soubor:')
			->setHtmlAttribute('class', 'slide-filename');
		$container->addTextArea('speakerNotes', 'Poznámky:')
			->setRequired('Zadejte prosím poznámky');
	}

}
