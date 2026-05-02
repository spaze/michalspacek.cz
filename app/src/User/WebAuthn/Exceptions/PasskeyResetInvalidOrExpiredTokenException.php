<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Exceptions;

use Throwable;

final class PasskeyResetInvalidOrExpiredTokenException extends PasskeyResetException
{

	public function __construct(int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct('Invalid or expired reset token', $code, $previous);
	}

}
