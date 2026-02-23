<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Exceptions;

use Throwable;

final class TalkSlideNameDoesNotExistException extends TalkSlideDoesNotExistException
{

	public function __construct(int $talkId, string $slideName, ?Throwable $previous = null)
	{
		parent::__construct("Talk id $talkId doesn't have a slide '{$slideName}'", previous: $previous);
	}

}
