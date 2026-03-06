<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Exceptions;

use Throwable;

final class TalkSlidesNotPublishedException extends TalkException
{

	public function __construct(int $talkId, ?Throwable $previous = null)
	{
		parent::__construct("Slides not published for talk id $talkId", previous: $previous);
	}

}
