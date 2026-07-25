<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils\Exceptions;

use RuntimeException;
use Throwable;

final class EmptyFilenameException extends RuntimeException
{

	public function __construct(?Throwable $previous = null)
	{
		parent::__construct('The filename is an empty string', previous: $previous);
	}

}
