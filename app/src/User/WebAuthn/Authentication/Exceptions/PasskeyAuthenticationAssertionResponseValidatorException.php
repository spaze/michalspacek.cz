<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Authentication\Exceptions;

use Throwable;

final class PasskeyAuthenticationAssertionResponseValidatorException extends PasskeyAuthenticationException
{

	public function __construct(int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct('An error occurred while checking the authenticator assertion response', $code, $previous);
	}

}
