<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Exceptions;

use RuntimeException;
use Throwable;

final class HttpClientRequestUnsupportedSchemeException extends RuntimeException
{

	public function __construct(string $url, ?string $scheme, ?Throwable $previous = null)
	{
		$message = $scheme === null
			? "Only http(s) URLs are allowed, '{$url}' has no scheme"
			: "Only http(s) URLs are allowed, '{$url}' has scheme '{$scheme}'";
		parent::__construct($message, previous: $previous);
	}

}
