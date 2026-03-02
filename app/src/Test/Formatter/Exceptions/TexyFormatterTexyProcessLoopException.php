<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Formatter\Exceptions;

use LogicException;
use MichalSpacekCz\Formatter\TexyFormatter;
use Throwable;

final class TexyFormatterTexyProcessLoopException extends LogicException
{

	public function __construct(?Throwable $previous = null)
	{
		$message = sprintf('Texy shortcut handler must not use %s, otherwise "Processing is in progress yet" will be thrown in %s::format*() methods', TexyFormatter::class, TexyFormatter::class);
		parent::__construct($message, previous: $previous);
	}

}
