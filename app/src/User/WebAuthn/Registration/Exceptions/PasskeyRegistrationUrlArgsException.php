<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration\Exceptions;

use Nette\CommandLine\ParseException;

final class PasskeyRegistrationUrlArgsException extends PasskeyRegistrationUrlException
{

	public function __construct(ParseException $parseException)
	{
		parent::__construct($parseException->getMessage(), previous: $parseException);
	}

}
