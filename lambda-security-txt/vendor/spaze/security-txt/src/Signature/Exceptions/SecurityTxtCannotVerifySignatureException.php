<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature\Exceptions;

use Spaze\SecurityTxt\Signature\SecurityTxtSignatureErrorInfo;
use Throwable;

final class SecurityTxtCannotVerifySignatureException extends SecurityTxtSignatureErrorInfoException
{

	public function __construct(?string $message, SecurityTxtSignatureErrorInfo $errorInfo, ?Throwable $previous = null)
	{
		parent::__construct($message === null ? 'Cannot verify signature' : "Cannot verify signature: {$message}", $errorInfo, $previous);
	}

}
