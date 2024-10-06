<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime\Exceptions;

use Throwable;

class InvalidTimezoneException extends DateTimeException
{

	public function __construct(string $timezone, ?Throwable $previous = null)
	{
		parent::__construct("Invalid timezone '{$timezone}'", previous: $previous);
	}

}
