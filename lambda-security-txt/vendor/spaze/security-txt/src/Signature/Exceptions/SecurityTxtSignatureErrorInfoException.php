<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature\Exceptions;

use Spaze\SecurityTxt\Signature\SecurityTxtSignatureErrorInfo;
use Throwable;

abstract class SecurityTxtSignatureErrorInfoException extends SecurityTxtSignatureException
{

	public function __construct(
		string $message,
		private readonly SecurityTxtSignatureErrorInfo $errorInfo,
		?Throwable $previous = null,
	) {
		$message = sprintf(
			'%s: %s; code: %s, source: %s, library message: %s',
			$message,
			$errorInfo->getMessageAsString(),
			$errorInfo->getCodeAsString(),
			$errorInfo->getSourceAsString(),
			$errorInfo->getLibraryMessageAsString(),
		);
		parent::__construct($message, $errorInfo->getCode() ?? 0, previous: $previous);
	}


	public function getErrorInfo(): SecurityTxtSignatureErrorInfo
	{
		return $this->errorInfo;
	}

}
