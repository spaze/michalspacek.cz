<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

use Nette\Forms\Controls\UploadControl;

class VideoThumbnailFileUploads
{

	public function __construct(
		private readonly UploadControl $videoThumbnail,
		private readonly UploadControl $videoThumbnailAlternative,
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

}
