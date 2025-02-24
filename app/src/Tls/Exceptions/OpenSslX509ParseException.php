<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls\Exceptions;

use Exception;
use Throwable;

final class OpenSslX509ParseException extends Exception
{

	public function __construct(string $serializedInfo, ?Throwable $previous = null)
	{
		$serializedInfo = str_replace("\n", '\n', $serializedInfo);
		parent::__construct(sprintf("Required keys missing in the returned array: '%s'", $serializedInfo), previous: $previous);
	}

}
