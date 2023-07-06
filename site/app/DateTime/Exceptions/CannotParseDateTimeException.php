<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime\Exceptions;

use Throwable;

class CannotParseDateTimeException extends DateTimeException
{

	public function __construct(string $format, string $datetime, ?Throwable $previous = null)
	{
		parent::__construct("Cannot parse '{$datetime}' using format '{$format}'", $previous);
	}

}
