<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks\Exceptions;

use Exception;
use Throwable;

final class TalkDateTimeException extends Exception
{

	public function __construct(string $date, ?Throwable $previous = null)
	{
		parent::__construct("Unsupported date format '$date'", previous: $previous);
	}

}
