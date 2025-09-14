<?php
declare(strict_types = 1);

namespace Spaze\Session\Exceptions;

use Exception;
use Throwable;

class SessionColumnUnexpectedTypeException extends Exception
{

	public function __construct(string $column, string $type, string $expected, ?Throwable $previous = null)
	{
		parent::__construct("Session table column {$column} is {$type}, should be {$expected}", previous: $previous);
	}

}
