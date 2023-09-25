<?php
declare(strict_types = 1);

namespace MichalSpacekCz\UpcKeys\Exceptions;

use Throwable;

class UpcKeysApiIncorrectTokensException extends UpcKeysApiException
{

	public function __construct(string $json, string $line, ?Throwable $previous = null)
	{
		parent::__construct("{$json} ({$line})", previous: $previous);
	}

}
