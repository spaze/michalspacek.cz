<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Talks\Exceptions\DuplicatedSlideException;
use MichalSpacekCz\Talks\Talks;
use Nette\Application\Request;
use Nette\Application\UI\Form;
use Nette\Database\Row;
use Nette\Forms\Container;
use Nette\Http\FileUpload;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;

class TalkSlidesFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly Talks $talks,
		private readonly TexyFormatter $texyFormatter,
	) {
	}


	/**
	 * @param callable(Html, string, int): void $onSuccess
	 * @param int $talkId
	 * @param Row[] $slides
	 * @param int $newCount
	 * @param int $maxSlideUploads
	 * @param Request $request
	 * @return Form
	 */
	public function create(callable $onSuccess, int $talkId, array $slides, int $newCount, int $maxSlideUploads, Request $request): Form
	{
		$form = $this->factory->create();
		$slidesContainer = $form->addContainer('slides');
		foreach ($slides as $slide) {
			$slideIdContainer = $slidesContainer->addContainer($slide->slideId);
			$this->addSlideFields($form, $slideIdContainer, $slide->filenamesTalkId);
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
		$newContainer = $form->addContainer('new');
		for ($i = 0; $i < $newCount; $i++) {
			$newIdContainer = $newContainer->addContainer($i);
			$this->addSlideFields($form, $newIdContainer, null);
		}

		$form->addCheckbox('deleteReplaced', 'Smazat nahrazené soubory?');
		$form->addSubmit('submit', 'Upravit');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($slides, $onSuccess, $talkId): void {
			try {
				$this->talks->saveSlides($talkId, $slides, $values);
				$message = $this->texyFormatter->translate('messages.talks.admin.slideadded');
				$type = 'info';
			} catch (DuplicatedSlideException $e) {
				$message = $this->texyFormatter->translate('messages.talks.admin.duplicatealias', [(string)$e->getLastUniqueNumber()]);
				$type = 'error';
			}
			$onSuccess($message, $type, $talkId);
		};
		$form->onValidate[] = function (Form $form) use ($request, $maxSlideUploads): void {
			// Check whether max allowed file uploads has been reached
			$uploaded = 0;
			$files = $request->getFiles();
			array_walk_recursive($files, function ($item) use (&$uploaded) {
				if ($item instanceof FileUpload) {
					$uploaded++;
				}
			});
			// If there's no error yet then the number of uploaded just coincidentally matches max allowed
			if ($form->hasErrors() && $uploaded >= $maxSlideUploads) {
				$form->addError($this->texyFormatter->translate('messages.talks.admin.maxslideuploadsexceeded', [(string)$maxSlideUploads]));
			}
		};

		return $form;
	}


	private function addSlideFields(Form $form, Container $container, ?int $filenamesTalkId): void
	{
		$disableSlideUploads = (bool)$filenamesTalkId;
		$container->addText('alias', 'Alias:')
			->setRequired('Zadejte prosím alias')
			->addRule($form::PATTERN, 'Alias musí být ve formátu [_.,a-z0-9-]+', '[_.,a-z0-9-]+');
		$container->addText('number', 'Slajd:')
			->setHtmlType('number')
			->setDefaultValue(1)
			->setHtmlAttribute('class', 'right slide-nr')
			->setRequired('Zadejte prosím číslo slajdu');
		$container->addText('title', 'Titulek:')
			->setRequired('Zadejte prosím titulek');
		$upload = $container->addUpload('replace', 'Nahradit:')
			->setDisabled($disableSlideUploads)
			->setHtmlAttribute('title', 'Nahradit soubor (*.' . implode(', *.', $this->talks->getSupportedImages()) . ')')
			->setHtmlAttribute('accept', implode(',', array_keys($this->talks->getSupportedImages())));
		$container->addText('filename', 'Soubor:')
			->setDisabled($disableSlideUploads)
			->setHtmlAttribute('class', 'slide-filename')
			->addConditionOn($upload, $form::BLANK)
				->setRequired('Zadejte prosím soubor');
		$container->addUpload('replaceAlternative', 'Nahradit:')
			->setDisabled($disableSlideUploads)
			->setHtmlAttribute('title', 'Nahradit alternativní soubor (*.' . implode(', *.', $this->talks->getSupportedAlternativeImages()) . ')')
			->setHtmlAttribute('accept', implode(',', array_keys($this->talks->getSupportedAlternativeImages())));
		$container->addText('filenameAlternative', 'Soubor:')
			->setDisabled($disableSlideUploads)
			->setHtmlAttribute('class', 'slide-filename');
		$container->addTextArea('speakerNotes', 'Poznámky:')
			->setRequired('Zadejte prosím poznámky');
	}

}
