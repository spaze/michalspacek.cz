<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\Resources\MediaResources;
use Nette\Database\Row;

readonly class VideoFactory
{

	public function __construct(
		private MediaResources $mediaResources,
		private SupportedImageFileFormats $supportedImageFileFormats,
		private VideoThumbnails $videoThumbnails,
	) {
	}


	/**
	 * @throws ContentTypeException
	 */
	public function createFromDatabaseRow(Row $row): Video
	{
		return new Video(
			$row->videoHref,
			$row->videoThumbnail,
			$row->videoThumbnail ? $this->mediaResources->getImageUrl($row->id, $row->videoThumbnail) : null,
			$row->videoThumbnailAlternative,
			$row->videoThumbnailAlternative ? $this->mediaResources->getImageUrl($row->id, $row->videoThumbnailAlternative) : null,
			$row->videoThumbnailAlternative ? $this->supportedImageFileFormats->getAlternativeContentTypeByExtension(pathinfo($row->videoThumbnailAlternative, PATHINFO_EXTENSION)) : null,
			$this->videoThumbnails->getWidth(),
			$this->videoThumbnails->getHeight(),
			$row->videoHref ? VideoPlatform::tryFromUrl($row->videoHref)?->getName() : null,
		);
	}

}
