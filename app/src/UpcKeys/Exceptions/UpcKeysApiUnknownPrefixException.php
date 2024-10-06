<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys\Exceptions;

use Throwable;

class UpcKeysApiUnknownPrefixException extends UpcKeysApiException
{

	public function __construct(string $serial, ?Throwable $previous = null)
	{
		parent::__construct('Unknown prefix for serial ' . $serial, previous: $previous);
	}

}
