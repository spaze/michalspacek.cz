<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Exceptions;

use OutOfRangeException;
use Spaze\Encryption\Format\LogSafeValue;
use Throwable;

class MissingSecretKeyException extends OutOfRangeException
{

	public function __construct(string $keyId, ?Throwable $previous = null)
	{
		parent::__construct("No secret key configured for key id '" . LogSafeValue::from($keyId) . "', it can only be used to encrypt", previous: $previous);
	}

}
