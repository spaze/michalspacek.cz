<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Exceptions;

use Throwable;

class TalkSlideDoesNotExistException extends TalkException
{

	public function __construct(int $talkId, int|string $slide, ?Throwable $previous = null)
	{
		$desc = is_int($slide) ? "number $slide" : "'$slide'";
		parent::__construct("Talk id $talkId doesn't have a slide {$desc}", previous: $previous);
	}

}
