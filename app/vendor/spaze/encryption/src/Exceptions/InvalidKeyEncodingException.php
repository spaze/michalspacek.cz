<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Exceptions;

use Exception;
use Spaze\Encryption\Format\LogSafeValue;
use Throwable;

class InvalidKeyEncodingException extends Exception
{

	public function __construct(string $id, ?Throwable $previous = null)
	{
		parent::__construct("Key '" . LogSafeValue::from($id) . "' is not a valid hex-encoded string", previous: $previous);
	}

}
