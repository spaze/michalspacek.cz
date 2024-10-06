<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Exceptions;

use Throwable;

class SlideImageUploadFailedException extends TalkException
{

	public function __construct(int $error, ?Throwable $previous = null)
	{
		parent::__construct("Slide image upload failed, error '{$error}'", previous: $previous);
	}

}
