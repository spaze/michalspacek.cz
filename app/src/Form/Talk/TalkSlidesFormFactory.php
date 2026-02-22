<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Talk;

use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Form\Validators\FormValidators;
use MichalSpacekCz\Form\Validators\FormValidatorTexyRuleFactory;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Media\SupportedImageFileFormats;
use MichalSpacekCz\Talks\Exceptions\DuplicatedSlideException;
use MichalSpacekCz\Talks\Slides\TalkSlideCollection;
use MichalSpacekCz\Talks\Slides\TalkSlides;
use Nette\Application\Request;
use Nette\Forms\Container;
use Nette\Forms\Form;
use Nette\Http\FileUpload;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;

final readonly class TalkSlidesFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private FormValidators $validators,
		private FormValidatorTexyRuleFactory $texyRuleFactory,
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
				assert($values->slides instanceof ArrayHash);
				assert($values->new instanceof ArrayHash);
				assert(is_bool($values->deleteReplaced));
				$this->talkSlides->saveSlides($talkId, $slides, $values->slides, $values->new, $values->deleteReplaced);
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
			array_walk_recursive($files, function (?FileUpload $file) use (&$uploaded) {
				if ($file !== null && $file->hasFile()) {
					$uploaded++;
				}
			});
			$maxSlideUploads = $this->getMaxSlideUploads();
			if ($uploaded > $maxSlideUploads) {
				$form->addError($this->texyFormatter->translate('messages.talks.admin.maxslideuploadsexceeded', [(string)$maxSlideUploads]));
			}
		};

		return $form;
	}


	private function addSlideFields(UiForm $form, Container $container, ?int $filenamesTalkId): void
	{
		$supportedImages = '*.' . implode(', *.', $this->supportedImageFileFormats->getMainExtensions());
		$supportedAlternativeImages = '*.' . implode(', *.', $this->supportedImageFileFormats->getAlternativeExtensions());
		$disableSlideUploads = (bool)$filenamesTalkId;
		$aliasInput = $container->addText('alias', 'Alias:')
			->setRequired('Zadejte prosím alias');
		$this->validators->addValidateSlugRules($aliasInput);
		$container->addInteger('number', 'Slajd:')
			->setDefaultValue(1)
			->setHtmlAttribute('class', 'right slide-nr')
			->setRequired('Zadejte prosím číslo slajdu');
		$container->addText('title', 'Titulek:')
			->setRequired('Zadejte prosím titulek');
		$upload = $container->addUpload('replace', 'Nahradit:')
			->setDisabled($disableSlideUploads)
			->addRule(Form::MimeType, "Soubor musí být obrázek typu {$supportedImages}", $this->supportedImageFileFormats->getMainContentTypes())
			->setHtmlAttribute('title', "Nahradit soubor ({$supportedImages})")
			->setHtmlAttribute('accept', implode(',', $this->supportedImageFileFormats->getMainContentTypes()));
		$container->addText('filename', 'Soubor:')
			->setDisabled($disableSlideUploads)
			->setHtmlAttribute('class', 'slide-filename')
			->addConditionOn($upload, Form::Blank)
				->setRequired('Zadejte prosím soubor');
		$container->addUpload('replaceAlternative', 'Nahradit:')
			->setDisabled($disableSlideUploads)
			->addRule(Form::MimeType, "Alternativní soubor musí být obrázek typu {$supportedAlternativeImages}", $this->supportedImageFileFormats->getAlternativeContentTypes())
			->setHtmlAttribute('title', "Nahradit alternativní soubor ({$supportedAlternativeImages})")
			->setHtmlAttribute('accept', implode(',', $this->supportedImageFileFormats->getAlternativeContentTypes()));
		$container->addText('filenameAlternative', 'Soubor:')
			->setDisabled($disableSlideUploads)
			->setHtmlAttribute('class', 'slide-filename');
		$texyRule = $this->texyRuleFactory->create();
		$container->addTextArea('speakerNotes', 'Poznámky:')
			->addRule($texyRule->getRule(), $texyRule)
			->setRequired('Zadejte prosím poznámky');
	}


	public function getMaxSlideUploads(): int
	{
		// To catch the "uploaded more files than allowed" error we allow 𝑛+1 (max_file_uploads) files on the PHP level,
		// but only 𝑛 on the app level. It's difficult if not impossible, to distinguish between the error
		// and "uploaded all the files but not more than max" if those two numbers would be the same.
		return (int)floor((int)ini_get('max_file_uploads') / 2) * 2;
	}

}
