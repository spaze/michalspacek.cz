<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Exceptions;

use Exception;
use Throwable;

class HttpClientTlsCertificateNotAvailableException extends Exception
{

	public function __construct(string $url, ?Throwable $previous = null)
	{
		parent::__construct("Can't get TLS certificate because the request is not HTTPS: {$url}", previous: $previous);
	}

}
