<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Exceptions;

use Exception;
use Throwable;

class InvalidKeyPrefixException extends Exception
{

	public function __construct(string $id, string $prefix, ?string $keyStartsWith, ?Throwable $previous = null)
	{
		parent::__construct($keyStartsWith === null ? "Key '{$id}' has no prefix" : "Key '{$id}' prefix is '{$keyStartsWith}' but it should be '{$prefix}'", previous: $previous);
	}

}
