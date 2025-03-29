<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils\Exceptions;

use Exception;
use Throwable;

final class Base64InvalidInputToDecodeException extends Exception
{

	public function __construct(string $encoded, ?Throwable $previous = null)
	{
		parent::__construct("Input contains character from outside the Base64 alphabet: '{$encoded}'", previous: $previous);
	}

}
