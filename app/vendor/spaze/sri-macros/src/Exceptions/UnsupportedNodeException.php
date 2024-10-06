<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Exceptions;

use LogicException;
use Throwable;

class UnsupportedNodeException extends LogicException
{

	public function __construct(string $unsupportedNode, Throwable $previous = null)
	{
		parent::__construct("Unsupported node '$unsupportedNode'", previous: $previous);
	}

}
