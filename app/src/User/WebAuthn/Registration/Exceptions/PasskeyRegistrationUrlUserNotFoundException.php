<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration\Exceptions;

use Throwable;

final class PasskeyRegistrationUrlUserNotFoundException extends PasskeyRegistrationUrlException
{

	public function __construct(private readonly string $username, ?Throwable $previous = null)
	{
		parent::__construct(previous: $previous);
	}


	public function getUsername(): string
	{
		return $this->username;
	}

}
