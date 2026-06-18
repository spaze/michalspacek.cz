<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration\Exceptions;

use Throwable;

final class PasskeyRegistrationOptionsSerializationException extends PasskeyRegistrationException
{

	public function __construct(int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct("Couldn't serialize registration options", $code, $previous);
	}

}
