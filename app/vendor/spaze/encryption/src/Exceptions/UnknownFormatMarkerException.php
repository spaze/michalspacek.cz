<?php
declare(strict_types = 1);

namespace Spaze\Encryption\Exceptions;

use OutOfBoundsException;
use Spaze\Encryption\Format\LogSafeValue;
use Throwable;

class UnknownFormatMarkerException extends OutOfBoundsException
{

	public function __construct(string $marker, ?Throwable $previous = null)
	{
		parent::__construct("Unknown format marker '" . LogSafeValue::from($marker) . "', is the data corrupted, or encrypted by a newer version of this library?", previous: $previous);
	}

}
