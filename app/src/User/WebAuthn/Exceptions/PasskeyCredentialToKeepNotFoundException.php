<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Exceptions;

use Throwable;

final class PasskeyCredentialToKeepNotFoundException extends PasskeyException
{

	public function __construct(int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct('Passkey credential to keep was not found, refusing to revoke the others', $code, $previous);
	}

}
