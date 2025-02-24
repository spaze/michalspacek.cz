<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media\Exceptions;

use Exception;
use Throwable;

final class CannotDeleteMediaException extends Exception
{

	public function __construct(string $message, string $filename, ?Throwable $previous = null)
	{
		parent::__construct("{$filename}: {$message}", previous: $previous);
	}

}
