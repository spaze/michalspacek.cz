<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration\Exceptions;

use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyException;
use Throwable;

final class PasskeyRegistrationUserMismatchException extends PasskeyException
{

	public function __construct(int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct('Passkey registration token does not belong to the signed-in user', $code, $previous);
	}

}
