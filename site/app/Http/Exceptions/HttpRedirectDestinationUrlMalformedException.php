<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Exceptions;

use Exception;
use Throwable;

class HttpRedirectDestinationUrlMalformedException extends Exception
{

	public function __construct(string $destination, ?Throwable $previous = null)
	{
		parent::__construct("Redirect destination '$destination' is a seriously malformed URL", previous: $previous);
	}

}
