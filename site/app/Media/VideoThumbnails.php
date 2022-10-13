<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

use MichalSpacekCz\Media\Exceptions\CannotDeleteMediaException;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\Resources\MediaResources;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\UploadControl;
use Nette\Http\FileUpload;
use Nette\Utils\Callback;
use Nette\Utils\ImageException;
use stdClass;

class VideoThumbnails
{

	private const VIDEO_THUMBNAIL_WIDTH = 320;
	private const VIDEO_THUMBNAIL_HEIGHT = 180;


	public function __construct(
		private readonly MediaResources $mediaResources,
		private readonly SupportedImageFileFormats $supportedImageFileFormats,
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


	public function addFormFields(Form $form, bool $hasMainVideoThumbnail, bool $hasAlternativeVideoThumbnail): VideoThumbnailFormFields
	{
		$supportedImages = '*.' . implode(', *.', $this->supportedImageFileFormats->getMainExtensions());
		$supportedAlternativeImages = '*.' . implode(', *.', $this->supportedImageFileFormats->getAlternativeExtensions());
		$videoThumbnail = $form->addUpload('videoThumbnail', 'Video náhled:')
			->addRule($form::MIME_TYPE, "%label musí být obrázek typu {$supportedImages}", $this->supportedImageFileFormats->getMainContentTypes())
			->setHtmlAttribute('title', "Vyberte soubor ({$supportedImages})")
			->setHtmlAttribute('accept', implode(',', $this->supportedImageFileFormats->getMainContentTypes()));
		$videoThumbnailAlternative = $form->addUpload('videoThumbnailAlternative', 'Alternativní video náhled:')
			->addRule($form::MIME_TYPE, "%label musí být obrázek typu {$supportedAlternativeImages}", $this->supportedImageFileFormats->getAlternativeContentTypes())
			->setHtmlAttribute('title', "Vyberte alternativní soubor ({$supportedAlternativeImages})")
			->setHtmlAttribute('accept', implode(',', $this->supportedImageFileFormats->getAlternativeContentTypes()));
		if ($hasMainVideoThumbnail) {
			$form->addCheckbox('removeVideoThumbnail', 'Odstranit')
				->addCondition($form::FILLED, true)
				->toggle('#videoThumbnailFormField', false)
				->addConditionOn($videoThumbnail, $form::FILLED, true)
				->addRule($form::BLANK, 'Nelze zároveň nahrávat a mazat video náhled');
			$videoThumbnail->addCondition($form::FILLED, true)
				->toggle('#currentVideoThumbnail', false);
		}
		if ($hasAlternativeVideoThumbnail) {
			$form->addCheckbox('removeVideoThumbnailAlternative', 'Odstranit')
				->addCondition($form::FILLED, true)
				->toggle('#videoThumbnailAlternativeFormField', false)
				->addConditionOn($videoThumbnailAlternative, $form::FILLED, true)
				->addRule($form::BLANK, 'Nelze zároveň nahrávat a mazat alternativní video náhled');
			$videoThumbnailAlternative->addCondition($form::FILLED, true)
				->toggle('#currentVideoThumbnailAlternative', false);
		}
		return new VideoThumbnailFormFields($videoThumbnail, $videoThumbnailAlternative);
	}


	public function addOnValidateUploads(Form $form, VideoThumbnailFormFields $formFields): void
	{
		$form->onValidate[] = function (Form $form, stdClass $values) use ($formFields): void {
			$this->validateUpload($values->videoThumbnail, $formFields->getVideoThumbnail());
			$this->validateUpload($values->videoThumbnailAlternative, $formFields->getVideoThumbnailAlternative());
		};
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
	public function getUploadedMainFileBasename(stdClass $values): ?string
	{
		return $this->getUploadedFileBasename($values->videoThumbnail, $this->supportedImageFileFormats->getMainExtensionByContentType(...));
	}


	/**
	 * @throws ContentTypeException
	 */
	public function getUploadedAlternativeFileBasename(stdClass $values): ?string
	{
		return $this->getUploadedFileBasename($values->videoThumbnailAlternative, $this->supportedImageFileFormats->getAlternativeExtensionByContentType(...));
	}


	/**
	 * @param FileUpload $thumbnail
	 * @param callable(string): string $getExtension
	 * @return string|null
	 * @throws ContentTypeException
	 */
	private function getUploadedFileBasename(FileUpload $thumbnail, callable $getExtension): ?string
	{
		if (!$thumbnail->isOk()) {
			return null;
		}
		$contentType = $thumbnail->getContentType();
		if (!$contentType) {
			throw new ContentTypeException();
		}
		return 'video-thumbnail.' . $getExtension($contentType);
	}


	/**
	 * @throws ContentTypeException
	 */
	public function saveVideoThumbnailFiles(int $id, stdClass $values): void
	{
		$basename = $this->getUploadedMainFileBasename($values);
		if ($basename) {
			$values->videoThumbnail->move($this->mediaResources->getImageFilename($id, $basename));
		}
		$basename = $this->getUploadedAlternativeFileBasename($values);
		if ($basename) {
			$values->videoThumbnailAlternative->move($this->mediaResources->getImageFilename($id, $basename));
		}
	}

}
