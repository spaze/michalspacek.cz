<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Exceptions;

use Throwable;

final class TalkSlideNumberDoesNotExistException extends TalkSlideDoesNotExistException
{

	public function __construct(int $talkId, int $slideNumber, ?Throwable $previous = null)
	{
		parent::__construct("Talk id $talkId doesn't have a slide number {$slideNumber}", previous: $previous);
	}

}
