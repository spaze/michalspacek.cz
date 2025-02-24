<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Exceptions;

use Exception;
use Throwable;

final class NoOriginalRequestException extends Exception
{

	public function __construct(?Throwable $previous = null)
	{
		parent::__construct('No original request', previous: $previous);
	}

}
