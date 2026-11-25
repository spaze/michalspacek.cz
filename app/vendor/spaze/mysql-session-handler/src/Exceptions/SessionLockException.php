<?php
declare(strict_types = 1);

namespace Spaze\Session\Exceptions;

use Exception;
use Throwable;

class SessionLockException extends Exception
{

	public function __construct(int $timeout, bool $timedOut, ?Throwable $previous = null)
	{
		$message = $timedOut ? "Session lock not acquired within the {$timeout} s timeout" : 'Session lock not acquired, an error occurred';
		parent::__construct($message, previous: $previous);
	}

}
