<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Authentication\Exceptions;

use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyServerException;
use Throwable;

final class PasskeyReauthenticationUserMismatchException extends PasskeyAuthenticationException implements PasskeyServerException
{

	public function __construct(int $signedInUserId, int $assertedUserId, int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct("Reauthentication passkey belongs to user {$assertedUserId}, not the signed-in user {$signedInUserId}", $code, $previous);
	}

}
