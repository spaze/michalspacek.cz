<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\Exceptions;

use Throwable;

class IdentityWithoutUsernameException extends IdentityException
{

	public function __construct(?Throwable $previous = null)
	{
		parent::__construct('Identity without username', previous: $previous);
	}

}
