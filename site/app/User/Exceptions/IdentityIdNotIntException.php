<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\Exceptions;

use Throwable;

class IdentityIdNotIntException extends IdentityException
{

	public function __construct(string $type, ?Throwable $previous = null)
	{
		parent::__construct("Identity id is of type {$type}, not an integer", previous: $previous);
	}

}
