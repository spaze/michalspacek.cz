<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime\Exceptions;

use Throwable;

final class CannotCreateDateTimeObjectException extends DateTimeException
{

	public function __construct(?Throwable $previous = null)
	{
		parent::__construct('Cannot create a DateTime or DateTimeImmutable object', $previous);
	}

}
