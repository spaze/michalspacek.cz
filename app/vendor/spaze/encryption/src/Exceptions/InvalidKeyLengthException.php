<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Exceptions;

use Exception;
use Spaze\Encryption\Format\LogSafeValue;
use Throwable;

class InvalidKeyLengthException extends Exception
{

	public function __construct(string $id, int $actualLength, int $expectedLength, ?Throwable $previous = null)
	{
		$expectedHexChars = $expectedLength * 2;
		parent::__construct("Key '" . LogSafeValue::from($id) . "' must be {$expectedLength} bytes ({$expectedHexChars} hexadecimal characters) but is {$actualLength} bytes", previous: $previous);
	}

}
