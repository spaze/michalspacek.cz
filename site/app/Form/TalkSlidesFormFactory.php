<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Media\SupportedImageFileFormats;
use MichalSpacekCz\Talks\Exceptions\DuplicatedSlideException;
use MichalSpacekCz\Talks\TalkSlides;
use Nette\Application\Request;
use Nette\Application\UI\Form;
use Nette\Database\Row;
use Nette\Forms\Container;
use Nette\Utils\Html;

class TalkSlidesFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly TalkSlides $talkSlides,
		private readonly TexyFormatter $texyFormatter,
		private readonly SupportedImageFileFormats $supportedImageFileFormats,
	) {
	}


	/**
	 * @param callable(Html, string, int): void $onSuccess
	 * @param int $talkId
	 * @param Row[] $slides
	 * @param int $newCount
	 * @param Request $request
	 * @return Form
	 */
	public function create(callable $onSuccess, int $talkId, array $slides, int $newCount, Request $request): Form
	{
		$form = $this->factory->create();
		$slidesContainer = $form->addContainer('slides');
		foreach ($slides as $slide) {
			$slideIdContainer = $slidesContainer->addContainer($slide->slideId);
			$this->addSlideFields($form, $slideIdContainer, $slide->filenamesTalkId);
			$values = [
				'alias' => $slide->alias,
				'number' => $slide->number,
				'title' => $slide->title,
				'filename' => $slide->filename,
				'filenameAlternative' => $slide->filenameAlternative,
				'speakerNotes' => $slide->speakerNotesTexy,
			];
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

		$form->onSuccess[] = function (Form $form) use ($slides, $onSuccess, $talkId): void {
			try {
				$this->talkSlides->saveSlides($talkId, $slides, $form->getValues());
				$message = $this->texyFormatter->translate('messages.talks.admin.slideadded');
				$type = 'info';
			} catch (DuplicatedSlideException $e) {
				$message = $this->texyFormatter->translate('messages.talks.admin.duplicatealias', [(string)$e->getLastUniqueNumber()]);
				$type = 'error';
			}
			$onSuccess($message, $type, $talkId);
		};
		$form->onValidate[] = function (Form $form) use ($request): void {
			// Check whether max allowed file uploads has been reached
			$uploaded = 0;
			$files = $request->getFiles();
			array_walk_recursive($files, function () use (&$uploaded) {
				$uploaded++;
			});
			// If there's no error yet then the number of uploaded just coincidentally matches max allowed
			if ($form->hasErrors() && $uploaded >= $this->getMaxSlideUploads()) {
				$form->addError($this->texyFormatter->translate('messages.talks.admin.maxslideuploadsexceeded', [(string)$this->getMaxSlideUploads()]));
			}
		};

		return $form;
	}


	private function addSlideFields(Form $form, Container $container, ?int $filenamesTalkId): void
	{
		$supportedImages = '*.' . implode(', *.', $this->supportedImageFileFormats->getMainExtensions());
		$supportedAlternativeImages = '*.' . implode(', *.', $this->supportedImageFileFormats->getAlternativeExtensions());
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
			->addRule($form::MIME_TYPE, "Soubor musí být obrázek typu {$supportedImages}", $this->supportedImageFileFormats->getMainContentTypes())
			->setHtmlAttribute('title', "Nahradit soubor ({$supportedImages})")
			->setHtmlAttribute('accept', implode(',', $this->supportedImageFileFormats->getMainContentTypes()));
		$container->addText('filename', 'Soubor:')
			->setDisabled($disableSlideUploads)
			->setHtmlAttribute('class', 'slide-filename')
			->addConditionOn($upload, $form::BLANK)
				->setRequired('Zadejte prosím soubor');
		$container->addUpload('replaceAlternative', 'Nahradit:')
			->setDisabled($disableSlideUploads)
			->addRule($form::MIME_TYPE, "Alternativní soubor musí být obrázek typu {$supportedAlternativeImages}", $this->supportedImageFileFormats->getAlternativeContentTypes())
			->setHtmlAttribute('title', "Nahradit alternativní soubor ({$supportedAlternativeImages})")
			->setHtmlAttribute('accept', implode(',', $this->supportedImageFileFormats->getAlternativeContentTypes()));
		$container->addText('filenameAlternative', 'Soubor:')
			->setDisabled($disableSlideUploads)
			->setHtmlAttribute('class', 'slide-filename');
		$container->addTextArea('speakerNotes', 'Poznámky:')
			->setRequired('Zadejte prosím poznámky');
	}


	public function getMaxSlideUploads(): int
	{
		return (int)ini_get('max_file_uploads');
	}

}
