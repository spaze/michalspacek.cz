<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature\Exceptions;

use Spaze\SecurityTxt\Signature\SecurityTxtSignatureErrorInfo;
use Throwable;

final class SecurityTxtCannotCreateSignatureException extends SecurityTxtSignatureErrorInfoException
{

	public function __construct(string $key, SecurityTxtSignatureErrorInfo $errorInfo, ?Throwable $previous = null)
	{
		parent::__construct("Cannot create a signature using key {$key}", $errorInfo, $previous);
	}

}
