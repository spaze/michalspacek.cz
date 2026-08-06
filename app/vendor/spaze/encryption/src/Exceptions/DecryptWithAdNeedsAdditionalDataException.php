<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Exceptions;

use InvalidArgumentException;
use Throwable;

class DecryptWithAdNeedsAdditionalDataException extends InvalidArgumentException
{

	public function __construct(?Throwable $previous = null)
	{
		parent::__construct("additionalData must not be empty; use decrypt() for values that are not context-bound", previous: $previous);
	}

}
