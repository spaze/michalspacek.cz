<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Database\Exceptions;

use Throwable;

final class TypedDatabaseTypeException extends TypedDatabaseException
{

	public function __construct(string $expectedType, mixed $value, ?Throwable $previous = null)
	{
		parent::__construct(sprintf('%s expected, %s given', $expectedType, get_debug_type($value)), previous: $previous);
	}

}
