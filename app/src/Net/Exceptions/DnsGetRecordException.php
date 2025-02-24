<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Net\Exceptions;

use Exception;
use Throwable;

final class DnsGetRecordException extends Exception
{

	public function __construct(string $message, ?Throwable $previous = null)
	{
		parent::__construct($message, previous: $previous);
	}

}
