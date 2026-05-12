<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Exceptions;

use Throwable;

final class PasskeyCredentialSignedInWithException extends PasskeyException
{

	public function __construct(int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct('Cannot delete the passkey credential used to sign in to the current session', $code, $previous);
	}

}
