<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature\Exceptions;

use Spaze\SecurityTxt\Signature\SecurityTxtSignatureErrorInfo;
use Throwable;

final class SecurityTxtUnusableSigningKeyException extends SecurityTxtSignatureErrorInfoException
{

	public function __construct(string $key, SecurityTxtSignatureErrorInfo $errorInfo, ?Throwable $previous = null)
	{
		parent::__construct("Unusable signing key {$key}", $errorInfo, $previous);
	}

}
