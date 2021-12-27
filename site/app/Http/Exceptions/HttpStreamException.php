<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Exceptions;

use Exception;
use Throwable;

class HttpStreamException extends Exception
{

	public function __construct(int $notificationCode, string $message, int $messageCode, ?Throwable $previous = null)
	{
		parent::__construct(trim($message) . " ({$notificationCode})", $messageCode, $previous);
	}

}
