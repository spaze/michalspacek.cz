<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature\Exceptions;

use Throwable;

final class SecurityTxtCannotVerifySignatureException extends SecurityTxtSignatureException
{

	public function __construct(string $message, ?Throwable $previous = null)
	{
		parent::__construct("Cannot verify signature: {$message}", previous: $previous);
	}

}
