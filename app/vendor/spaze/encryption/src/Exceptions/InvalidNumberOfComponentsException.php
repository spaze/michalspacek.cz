<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Exceptions;

use OutOfBoundsException;
use Throwable;

class InvalidNumberOfComponentsException extends OutOfBoundsException
{

	public function __construct(?Throwable $previous = null)
	{
		parent::__construct("Data format must be '\$keyId\$ciphertext'", previous: $previous);
	}

}
