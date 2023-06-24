<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls\Exceptions;

use Exception;
use Throwable;

class SomeCertificatesLoggedToFileException extends Exception
{

	public function __construct(?Throwable $previous = null)
	{
		parent::__construct('Error logging to database, some certificates logged to file instead', previous: $previous);
	}

}
