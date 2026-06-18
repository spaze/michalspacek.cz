<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Authentication\Exceptions;

use MichalSpacekCz\Utils\Base64;
use Throwable;

final class PasskeyAuthenticationUnknownCredentialException extends PasskeyAuthenticationException
{

	public function __construct(string $credentialId, int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct('Unknown credential id ' . Base64::urlEncode($credentialId), $code, $previous);
	}

}
