<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Exceptions;

use InvalidArgumentException;
use Throwable;

class EncryptWithAdNeedsAdditionalDataException extends InvalidArgumentException
{

	public function __construct(?Throwable $previous = null)
	{
		parent::__construct("additionalData must not be empty; use encrypt() for values that are not context-bound", previous: $previous);
	}

}
