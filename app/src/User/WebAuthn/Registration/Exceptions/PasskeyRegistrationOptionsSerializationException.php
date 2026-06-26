<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration\Exceptions;

use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyServerException;
use Throwable;

final class PasskeyRegistrationOptionsSerializationException extends PasskeyRegistrationException implements PasskeyServerException
{

	public function __construct(int $code = 0, ?Throwable $previous = null)
	{
		parent::__construct("Couldn't serialize registration options", $code, $previous);
	}

}
