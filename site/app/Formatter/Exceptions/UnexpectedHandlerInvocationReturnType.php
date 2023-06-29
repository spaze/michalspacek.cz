<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\Exceptions;

use Exception;
use Throwable;

class UnexpectedHandlerInvocationReturnType extends Exception
{

	public function __construct(mixed $result, ?Throwable $previous = null)
	{
		parent::__construct('Unexpected return type: ' . get_debug_type($result), previous: $previous);
	}

}
