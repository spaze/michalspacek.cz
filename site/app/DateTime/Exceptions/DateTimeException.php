<?php
declare(strict_types = 1);

namespace MichalSpacekCz\DateTime\Exceptions;

use Exception;
use Nette\Utils\Json;
use Throwable;

abstract class DateTimeException extends Exception
{

	public function __construct(string $message, Throwable $previous = null)
	{
		$message .= ' ' . Json::encode(date_get_last_errors());
		parent::__construct($message, 0, $previous);
	}

}
