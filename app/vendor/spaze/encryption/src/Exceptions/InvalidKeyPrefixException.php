<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Exceptions;

use Exception;
use Spaze\Encryption\Format\LogSafeValue;
use Throwable;

class InvalidKeyPrefixException extends Exception
{

	public function __construct(string $id, string $prefix, ?Throwable $previous = null)
	{
		parent::__construct("Key '" . LogSafeValue::from($id) . "' must start with '{$prefix}'", previous: $previous);
	}

}
