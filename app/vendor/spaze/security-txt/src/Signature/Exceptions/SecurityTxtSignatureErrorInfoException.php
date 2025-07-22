<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature\Exceptions;

use Spaze\SecurityTxt\Signature\SecurityTxtSignatureErrorInfo;
use Throwable;

abstract class SecurityTxtSignatureErrorInfoException extends SecurityTxtSignatureException
{

	public function __construct(string $message, SecurityTxtSignatureErrorInfo $errorInfo, ?Throwable $previous = null)
	{
		$message = sprintf(
			'%s: %s; code: %s, source: %s, library message: %s',
			$message,
			$errorInfo->getMessage() === false ? '<false>' : ($errorInfo->getMessage() === null ? '<null>' : $errorInfo->getMessage()),
			$errorInfo->getCode() ?? '<null>',
			$errorInfo->getSource() ?? '<null>',
			$errorInfo->getLibraryMessage() ?? '<null>',
		);
		parent::__construct($message, $errorInfo->getCode() ?? 0, previous: $previous);
	}

}
