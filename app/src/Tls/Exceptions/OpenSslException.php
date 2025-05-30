<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls\Exceptions;

use Exception;
use Throwable;

final class OpenSslException extends Exception
{

	public function __construct(?Throwable $previous = null)
	{
		$messages = [];
		while (($message = openssl_error_string()) !== false) {
			$messages[] = $message;
		}
		parent::__construct(implode('; ', $messages), 0, $previous);
	}

}
