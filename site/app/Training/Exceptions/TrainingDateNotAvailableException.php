<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Exceptions;

use Throwable;

class TrainingDateNotAvailableException extends TrainingDateException
{

	public function __construct(?Throwable $previous = null)
	{
		parent::__construct('Training date not available', previous: $previous);
	}

}
