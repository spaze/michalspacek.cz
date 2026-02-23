<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Exceptions;

use Throwable;

final class TalkSlideIdDoesNotExistException extends TalkSlideDoesNotExistException
{

	public function __construct(int $talkId, int $slideId, ?Throwable $previous = null)
	{
		parent::__construct("Talk id $talkId doesn't have a slide id {$slideId}", previous: $previous);
	}

}
