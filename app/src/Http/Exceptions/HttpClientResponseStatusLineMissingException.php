<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Exceptions;

use Exception;
use Throwable;

final class HttpClientResponseStatusLineMissingException extends Exception
{

	public function __construct(string $url, ?Throwable $previous = null)
	{
		parent::__construct("No status line in the response from {$url}", previous: $previous);
	}

}
