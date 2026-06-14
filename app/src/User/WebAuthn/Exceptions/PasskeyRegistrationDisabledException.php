<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Exceptions;

use Throwable;

final class PasskeyRegistrationDisabledException extends PasskeyException
{

	public function __construct(int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct('Passkey registration is disabled', $code, $previous);
	}

}
