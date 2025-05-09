<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature\Exceptions;

use Exception;
use Throwable;

final class SecurityTxtCannotVerifySignatureException extends Exception
{

	public function __construct(string $message, ?Throwable $previous = null)
	{
		parent::__construct("Cannot verify signature: {$message}", previous: $previous);
	}

}
