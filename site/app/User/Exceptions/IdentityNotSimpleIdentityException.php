<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\Exceptions;

use Exception;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;
use Throwable;

class IdentityNotSimpleIdentityException extends Exception
{

	public function __construct(?IIdentity $identity, ?Throwable $previous = null)
	{
		parent::__construct(
			sprintf('Identity is of class %s but should be %s', $identity === null ? '<null>' : $identity::class, SimpleIdentity::class),
			previous: $previous,
		);
	}

}
