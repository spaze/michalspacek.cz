<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

use Nette\Forms\Controls\UploadControl;

readonly class VideoThumbnailFileUploads
{

	public function __construct(
		private UploadControl $videoThumbnail,
		private UploadControl $videoThumbnailAlternative,
		private bool $hasVideoThumbnail,
		private bool $hasAlternativeVideoThumbnail,
	) {
	}


	public function getVideoThumbnail(): UploadControl
	{
		return $this->videoThumbnail;
	}


	public function getVideoThumbnailAlternative(): UploadControl
	{
		return $this->videoThumbnailAlternative;
	}


	public function hasVideoThumbnail(): bool
	{
		return $this->hasVideoThumbnail;
	}


	public function hasAlternativeVideoThumbnail(): bool
	{
		return $this->hasAlternativeVideoThumbnail;
	}

}
