<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys\Exceptions;

use Throwable;

class UpcKeysApiResponseInvalidException extends UpcKeysApiException
{

	public function __construct(?string $json = null, ?Throwable $previous = null)
	{
		parent::__construct($json, previous: $previous);
	}

}
