<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration\Exceptions;

use Throwable;

final class PasskeyRegistrationAttestationResponseValidatorException extends PasskeyRegistrationException
{

	public function __construct(int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct('An error occurred while checking the authenticator attestation response', $code, $previous);
	}

}
