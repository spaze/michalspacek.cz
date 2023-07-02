<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime\Exceptions;

use Exception;
use Throwable;

class InvalidTimezoneException extends Exception
{

	public function __construct(string $timezone, ?Throwable $previous = null)
	{
		parent::__construct("Invalid timezone '{$timezone}'", previous: $previous);
	}

}
