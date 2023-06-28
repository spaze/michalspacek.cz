<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Exceptions;

use Throwable;

class UnknownSlideException extends TalkException
{

	public function __construct(string $slide, int $talkId, ?Throwable $previous = null)
	{
		parent::__construct("Unknown slide '{$slide}' for talk id '{$talkId}'", previous: $previous);
	}

}
