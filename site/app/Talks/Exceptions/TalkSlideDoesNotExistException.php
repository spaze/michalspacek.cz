<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Exceptions;

use Throwable;

class TalkSlideDoesNotExistException extends TalkException
{

	public function __construct(int $talkId, int $number, ?Throwable $previous = null)
	{
		parent::__construct("Talk id $talkId doesn't have a slide number $number", previous: $previous);
	}

}
