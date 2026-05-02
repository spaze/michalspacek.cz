<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Exceptions;

use Throwable;

final class PasskeyAuthenticationCrossOriginAuthenticationException extends PasskeyAuthenticationException
{

	public function __construct(int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct('Cross-origin authentication rejected', $code, $previous);
	}

}
