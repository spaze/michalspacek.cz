<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration\Exceptions;

use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyException;
use Throwable;

final class PasskeyRegistrationInvalidOrExpiredTokenException extends PasskeyException
{

	public function __construct(int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct('Invalid or expired registration token', $code, $previous);
	}

}
