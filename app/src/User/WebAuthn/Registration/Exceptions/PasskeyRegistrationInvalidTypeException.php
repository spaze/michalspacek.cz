<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration\Exceptions;

use Throwable;

final class PasskeyRegistrationInvalidTypeException extends PasskeyRegistrationException
{

	public function __construct(int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct('Invalid response type', $code, $previous);
	}

}
