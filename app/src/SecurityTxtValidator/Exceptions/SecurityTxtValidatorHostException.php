<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Exceptions;

use Exception;
use Nette\Utils\Html;
use Throwable;

final class SecurityTxtValidatorHostException extends Exception
{

	public function __construct(
		public readonly Html $errorMessage,
		?Throwable $previous = null,
	) {
		parent::__construct($this->errorMessage->toText(), previous: $previous);
	}

}
