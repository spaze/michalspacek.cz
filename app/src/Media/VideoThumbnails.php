<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Media\Exceptions\CannotDeleteMediaException;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\Exceptions\MissingContentTypeException;
use MichalSpacekCz\Media\Resources\MediaResources;
use Nette\Forms\Controls\UploadControl;
use Nette\Forms\Form;
use Nette\Http\FileUpload;
use Nette\Utils\Callback;
use Nette\Utils\ImageException;

readonly class VideoThumbnails
{

	private const int VIDEO_THUMBNAIL_WIDTH = 320;
	private const int VIDEO_THUMBNAIL_HEIGHT = 180;


	public function __construct(
		private MediaResources $mediaResources,
		private SupportedImageFileFormats $supportedImageFileFormats,
	) {
	}


	public function getWidth(): int
	{
		return self::VIDEO_THUMBNAIL_WIDTH;
	}


	public function getHeight(): int
	{
		return self::VIDEO_THUMBNAIL_HEIGHT;
	}


	public function addFormFields(UiForm $form, bool $hasMainVideoThumbnail, bool $hasAlternativeVideoThumbnail): VideoThumbnailFileUploads
	{
		$supportedImages = '*.' . implode(', *.', $this->supportedImageFileFormats->getMainExtensions());
		$supportedAlternativeImages = '*.' . implode(', *.', $this->supportedImageFileFormats->getAlternativeExtensions());
		$videoThumbnail = $form->addUpload('videoThumbnail', 'Video náhled:')
			->addRule(Form::MimeType, "%label musí být obrázek typu {$supportedImages}", $this->supportedImageFileFormats->getMainContentTypes())
			->setHtmlAttribute('title', "Vyberte soubor ({$supportedImages})")
			->setHtmlAttribute('accept', implode(',', $this->supportedImageFileFormats->getMainContentTypes()));
		$videoThumbnailAlternative = $form->addUpload('videoThumbnailAlternative', 'Alternativní video náhled:')
			->addRule(Form::MimeType, "%label musí být obrázek typu {$supportedAlternativeImages}", $this->supportedImageFileFormats->getAlternativeContentTypes())
			->setHtmlAttribute('title', "Vyberte alternativní soubor ({$supportedAlternativeImages})")
			->setHtmlAttribute('accept', implode(',', $this->supportedImageFileFormats->getAlternativeContentTypes()));
		if ($hasMainVideoThumbnail) {
			$form->addCheckbox('removeVideoThumbnail', 'Odstranit')
				->addCondition(Form::Filled, true)
				->toggle('#videoThumbnailFormField', false)
				->addConditionOn($videoThumbnail, Form::Filled, true)
				->addRule(Form::Blank, 'Nelze zároveň nahrávat a mazat video náhled');
			$videoThumbnail->addCondition(Form::Filled, true)
				->toggle('#currentVideoThumbnail', false);
		}
		if ($hasAlternativeVideoThumbnail) {
			$form->addCheckbox('removeVideoThumbnailAlternative', 'Odstranit')
				->addCondition(Form::Filled, true)
				->toggle('#videoThumbnailAlternativeFormField', false)
				->addConditionOn($videoThumbnailAlternative, Form::Filled, true)
				->addRule(Form::Blank, 'Nelze zároveň nahrávat a mazat alternativní video náhled');
			$videoThumbnailAlternative->addCondition(Form::Filled, true)
				->toggle('#currentVideoThumbnailAlternative', false);
		}
		$form->onValidate[] = function (UiForm $form) use ($videoThumbnail, $videoThumbnailAlternative): void {
			$values = $form->getFormValues();
			assert($values->videoThumbnail instanceof FileUpload);
			assert($values->videoThumbnailAlternative instanceof FileUpload);
			$this->validateUpload($values->videoThumbnail, $videoThumbnail);
			$this->validateUpload($values->videoThumbnailAlternative, $videoThumbnailAlternative);
		};
		return new VideoThumbnailFileUploads($videoThumbnail, $videoThumbnailAlternative, $hasMainVideoThumbnail, $hasAlternativeVideoThumbnail);
	}


	private function validateUpload(FileUpload $upload, UploadControl $control): void
	{
		if ($upload->isOk()) {
			try {
				$image = $upload->toImage();
				if ($image->getWidth() !== $this->getWidth()) {
					$control->addError(sprintf('Obrázek musí mít šířku %d px', $this->getWidth()));
				}
				if ($image->getHeight() !== $this->getHeight()) {
					$control->addError(sprintf('Obrázek musí mít výšku %d px', $this->getHeight()));
				}
			} catch (ImageException) {
				$control->addError(sprintf('Soubor %s nelze načíst jako obrázek', $upload->getUntrustedName()));
			}
		} elseif ($upload->hasFile()) {
			$control->addError(sprintf('Soubor %s se nepodařilo nahrát, chyba %d', $upload->getUntrustedName(), $upload->getError()));
		}
	}


	public function deleteFile(int $id, string $basename): void
	{
		$filename = $this->mediaResources->getImageFilename($id, $basename);
		Callback::invokeSafe('unlink', [$filename], function (string $message) use ($filename): void {
			throw new CannotDeleteMediaException($message, $filename);
		});
	}


	/**
	 * @throws ContentTypeException
	 */
	public function getUploadedMainFileBasename(FileUpload $thumbnail): ?string
	{
		return $this->getUploadedFileBasename($thumbnail, $this->supportedImageFileFormats->getMainExtensionByContentType(...));
	}


	/**
	 * @throws ContentTypeException
	 */
	public function getUploadedAlternativeFileBasename(FileUpload $thumbnail): ?string
	{
		return $this->getUploadedFileBasename($thumbnail, $this->supportedImageFileFormats->getAlternativeExtensionByContentType(...));
	}


	/**
	 * @param callable(string): string $getExtension
	 * @throws MissingContentTypeException
	 */
	private function getUploadedFileBasename(FileUpload $thumbnail, callable $getExtension): ?string
	{
		if (!$thumbnail->isOk()) {
			return null;
		}
		$contentType = $thumbnail->getContentType();
		if ($contentType === null) {
			throw new MissingContentTypeException();
		}
		return 'video-thumbnail.' . $getExtension($contentType);
	}


	/**
	 * @throws ContentTypeException
	 */
	public function saveVideoThumbnailFiles(int $id, FileUpload $videoThumbnail, FileUpload $videoThumbnailAlternative): void
	{
		$basename = $this->getUploadedMainFileBasename($videoThumbnail);
		if ($basename !== null) {
			$videoThumbnail->move($this->mediaResources->getImageFilename($id, $basename));
		}
		$basename = $this->getUploadedAlternativeFileBasename($videoThumbnailAlternative);
		if ($basename !== null) {
			$videoThumbnailAlternative->move($this->mediaResources->getImageFilename($id, $basename));
		}
	}

}
