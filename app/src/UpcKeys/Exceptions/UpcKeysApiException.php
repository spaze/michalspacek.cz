<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys\Exceptions;

use Exception;
use Throwable;

abstract class UpcKeysApiException extends Exception
{

	public function __construct(?string $message = null, int $code = 0, ?Throwable $previous = null)
	{
		$exceptionMessage = 'Invalid API response';
		if ($message !== null) {
			$exceptionMessage .= ": {$message}";
		}
		parent::__construct($exceptionMessage, $code, $previous);
	}

}
