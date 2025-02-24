<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Exceptions;

use Exception;
use Throwable;

final class HttpClientRequestException extends Exception
{

	public function __construct(string $url, int $code = 0, ?Throwable $previous = null)
	{
		$message = "Can't get result from {$url}";
		if ($code !== 0) {
			$message .= ": {$code}";
		}
		parent::__construct($message, $code, $previous);
	}

}
