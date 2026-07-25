<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Training;

use DateTimeInterface;
use MichalSpacekCz\Form\FormFactory;
use MichalSpacekCz\Training\Files\TrainingFiles;
use MichalSpacekCz\Utils\MimeType;
use Nette\Forms\Form;
use Nette\Http\FileUpload;
use Nette\Utils\Html;

final readonly class TrainingFileFormFactory
{

	public function __construct(
		private FormFactory $factory,
		private TrainingFiles $trainingFiles,
	) {
	}


	/**
	 * @param callable(Html|string, string): void $onSuccess
	 * @param list<int> $applicationIdsAllowedFiles
	 */
	public function create(callable $onSuccess, DateTimeInterface $trainingStart, array $applicationIdsAllowedFiles): Form
	{
		$form = $this->factory->create();
		$allowedExtensions = $this->trainingFiles->getAllowedExtensions();
		$accept = [];
		foreach ($allowedExtensions as $extension) {
			$accept[] = ".{$extension}";
			// Images also get MIME type in the list, because some (mobile) pickers match images by type rather than by extension
			$mimeType = MimeType::getMimeTypeByExtension($extension);
			if ($mimeType !== null) {
				$accept[] = $mimeType;
			}
		}
		$upload = $form->addUpload('file', 'Soubor:')
			->setHtmlAttribute('accept', implode(',', $accept));
		$form->addSubmit('submit', 'Přidat');
		$form->onValidate[] = function () use ($upload, $allowedExtensions): void {
			$file = $upload->getValue();
			if (!$file instanceof FileUpload || !$file->hasFile()) {
				return;
			}
			$extension = pathinfo($file->getSanitizedName(), PATHINFO_EXTENSION);
			if (!$this->trainingFiles->isAllowedExtension($extension)) {
				// For an image the extension is rewritten by the detected MIME type and may differ from the uploaded filename ext
				$mime = $file->getContentType() ?? 'neznámý';
				$allowed = implode(', ', $allowedExtensions);
				$upload->addError("Nepodporovaný soubor (přípona {$extension}, typ {$mime}), povolené přípony: {$allowed}");
			}
		};
		$form->onSuccess[] = function (Form $form) use ($onSuccess, $trainingStart, $applicationIdsAllowedFiles): void {
			$values = $form->getValues();
			assert($values->file instanceof FileUpload);
			if ($values->file->isOk()) {
				$filename = $this->trainingFiles->addFile($trainingStart, $values->file, $applicationIdsAllowedFiles);
				$message = Html::el()->setText('Soubor ')
					->addHtml(Html::el('code')->setText($filename))
					->addHtml(Html::el()->setText(' byl přidán'));
				$onSuccess($message, 'info');
			} else {
				$onSuccess('Soubor nebyl vybrán nebo došlo k nějaké chybě při nahrávání', 'error');
			}
		};
		return $form;
	}

}
