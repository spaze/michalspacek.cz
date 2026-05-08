<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Exceptions;

use Throwable;

final class PasskeyResetUserNotFoundException extends PasskeyResetException
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
