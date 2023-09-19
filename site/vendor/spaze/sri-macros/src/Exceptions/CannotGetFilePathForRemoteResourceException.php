<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Exceptions;

use Exception;
use Throwable;

class CannotGetFilePathForRemoteResourceException extends Exception
{

	public function __construct(string $resource, ?Throwable $previous = null)
	{
		parent::__construct("Cannot get file path for remote resource $resource", previous: $previous);
	}

}
