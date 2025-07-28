<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Exceptions;

use LogicException;
use Throwable;

class UnsupportedOperatorException extends LogicException
{

	public function __construct(string $unsupportedOperator, string $supportedOperator, ?Throwable $previous = null)
	{
		parent::__construct("Unsupported resource operator '$unsupportedOperator', only supported: '$supportedOperator'", previous: $previous);
	}

}
