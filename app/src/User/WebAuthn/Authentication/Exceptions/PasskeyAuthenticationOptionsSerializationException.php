<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Authentication\Exceptions;

use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyServerException;
use Throwable;

final class PasskeyAuthenticationOptionsSerializationException extends PasskeyAuthenticationException implements PasskeyServerException
{

	public function __construct(int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct("Couldn't serialize authentication options", $code, $previous);
	}

}
