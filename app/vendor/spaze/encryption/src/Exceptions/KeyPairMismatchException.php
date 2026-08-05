<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Exceptions;

use Exception;
use Spaze\Encryption\Format\LogSafeValue;
use Throwable;

class KeyPairMismatchException extends Exception
{

	public function __construct(string $keyId, ?Throwable $previous = null)
	{
		$id = LogSafeValue::from($keyId);
		parent::__construct("Public key '{$id}' is not the public half of secret key '{$id}'", previous: $previous);
	}

}
