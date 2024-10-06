<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Media\SupportedImageFileFormats;
use MichalSpacekCz\Talks\Exceptions\DuplicatedSlideException;
use MichalSpacekCz\Talks\Slides\TalkSlideCollection;
use MichalSpacekCz\Talks\Slides\TalkSlides;
use Nette\Application\Request;
use Nette\Forms\Container;
use Nette\Utils\Html;

readonly class TalkSlidesFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private TalkSlides $talkSlides,
		private TexyFormatter $texyFormatter,
		private SupportedImageFileFormats $supportedImageFileFormats,
	) {
	}


	/**
	 * @param callable(Html, string, int): void $onSuccess
	 */
	public function create(callable $onSuccess, int $talkId, TalkSlideCollection $slides, int $newCount, Request $request): UiForm
	{
		$form = $this->factory->create();
		$slidesContainer = $form->addContainer('slides');
		foreach ($slides as $slide) {
			$slideIdContainer = $slidesContainer->addContainer($slide->getId());
			$this->addSlideFields($form, $slideIdContainer, $slide->getFilenamesTalkId());
			$values = [
				'alias' => $slide->getAlias(),
				'number' => $slide->getNumber(),
				'title' => $slide->getTitle(),
				'filename' => $slide->getFilename(),
				'filenameAlternative' => $slide->getFilenameAlternative(),
				'speakerNotes' => $slide->getSpeakerNotesTexy(),
			];
			$slideIdContainer->setDefaults($values);
		}

		if (count($slides) === 0 && $newCount === 0) {
			$newCount = 1;
		}
		$newContainer = $form->addContainer('new');
		for ($i = 0; $i < $newCount; $i++) {
			$newIdContainer = $newContainer->addContainer($i);
			$this->addSlideFields($form, $newIdContainer, null);
		}

		$form->addCheckbox('deleteReplaced', 'Smazat nahrazené soubory?');
		$form->addSubmit('submit', 'Upravit');

		$form->onSuccess[] = function (UiForm $form) use ($slides, $onSuccess, $talkId): void {
			try {
				$values = $form->getFormValues();
				$this->talkSlides->saveSlides($talkId, $slides, (array)$values->slides, array_values((array)$values->new), $values->deleteReplaced);
				$message = $this->texyFormatter->translate('messages.talks.admin.slideadded');
				$type = 'info';
			} catch (DuplicatedSlideException $e) {
				$message = $this->texyFormatter->translate('messages.talks.admin.duplicatealias', [(string)$e->getLastUniqueNumber()]);
				$type = 'error';
			}
			$onSuccess($message, $type, $talkId);
		};
		$form->onValidate[] = function (UiForm $form) use ($request): void {
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


	private function addSlideFields(UiForm $form, Container $container, ?int $filenamesTalkId): void
	{
		$supportedImages = '*.' . implode(', *.', $this->supportedImageFileFormats->getMainExtensions());
		$supportedAlternativeImages = '*.' . implode(', *.', $this->supportedImageFileFormats->getAlternativeExtensions());
		$disableSlideUploads = (bool)$filenamesTalkId;
		$container->addText('alias', 'Alias:')
			->setRequired('Zadejte prosím alias')
			->addRule($form::Pattern, 'Alias musí být ve formátu [_.,a-z0-9-]+', '[_.,a-z0-9-]+');
		$container->addInteger('number', 'Slajd:')
			->setHtmlType('number')
			->setDefaultValue(1)
			->setHtmlAttribute('class', 'right slide-nr')
			->setRequired('Zadejte prosím číslo slajdu');
		$container->addText('title', 'Titulek:')
			->setRequired('Zadejte prosím titulek');
		$upload = $container->addUpload('replace', 'Nahradit:')
			->setDisabled($disableSlideUploads)
			->addRule($form::MimeType, "Soubor musí být obrázek typu {$supportedImages}", $this->supportedImageFileFormats->getMainContentTypes())
			->setHtmlAttribute('title', "Nahradit soubor ({$supportedImages})")
			->setHtmlAttribute('accept', implode(',', $this->supportedImageFileFormats->getMainContentTypes()));
		$container->addText('filename', 'Soubor:')
			->setDisabled($disableSlideUploads)
			->setHtmlAttribute('class', 'slide-filename')
			->addConditionOn($upload, $form::Blank)
				->setRequired('Zadejte prosím soubor');
		$container->addUpload('replaceAlternative', 'Nahradit:')
			->setDisabled($disableSlideUploads)
			->addRule($form::MimeType, "Alternativní soubor musí být obrázek typu {$supportedAlternativeImages}", $this->supportedImageFileFormats->getAlternativeContentTypes())
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
