<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Exceptions;

use Throwable;
use UnexpectedValueException;

class SpammyApplicationException extends UnexpectedValueException
{

	public function __construct(Throwable $previous = null)
	{
		parent::__construct('Spammy application', 0, $previous);
	}

}
