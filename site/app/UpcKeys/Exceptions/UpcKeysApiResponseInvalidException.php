<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys\Exceptions;

use Exception;
use Throwable;

class UpcKeysApiResponseInvalidException extends Exception
{

	public function __construct(?string $json = null, ?Throwable $previous = null)
	{
		$message = 'Invalid API response';
		if ($json) {
			$message .= " {$json}";
		}
		parent::__construct($message, previous: $previous);
	}

}
