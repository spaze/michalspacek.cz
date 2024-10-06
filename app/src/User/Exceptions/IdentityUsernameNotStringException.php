<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\Exceptions;

use Throwable;

class IdentityUsernameNotStringException extends IdentityException
{

	public function __construct(string $type, ?Throwable $previous = null)
	{
		parent::__construct("Identity username is of type {$type}, not a string", previous: $previous);
	}

}
