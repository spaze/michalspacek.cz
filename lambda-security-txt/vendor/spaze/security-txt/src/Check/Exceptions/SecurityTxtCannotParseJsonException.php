<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Check\Exceptions;

use Exception;
use Throwable;

final class SecurityTxtCannotParseJsonException extends Exception
{

	public function __construct(string $message, ?Throwable $previous = null)
	{
		parent::__construct("Cannot parse JSON: {$message}", previous: $previous);
	}

}
