<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Exceptions;

use OutOfRangeException;
use Throwable;

class UnknownEncryptionKeyIdException extends OutOfRangeException
{

	public function __construct(string $keyId, ?Throwable $previous = null)
	{
		parent::__construct("Unknown encryption key id: '{$keyId}'", previous: $previous);
	}

}
