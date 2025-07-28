<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Exceptions;

use RuntimeException;
use Throwable;

class InvalidResourceAliasException extends RuntimeException
{

	public function __construct(?Throwable $previous = null)
	{
		parent::__construct('Invalid character in resource alias, using + with remote files or in direct mode?', previous: $previous);
	}

}
