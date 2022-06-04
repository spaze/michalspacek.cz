<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Exceptions;

use Exception;
use Throwable;

class CannotGetFileContentException extends Exception
{

	public function __construct(string $filename, ?Throwable $previous = null)
	{
		parent::__construct("Cannot get contents of $filename", previous: $previous);
	}

}
