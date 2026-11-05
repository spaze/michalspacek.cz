<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration\Exceptions;

use Nette\CommandLine\ParseException;

final class PasskeyRegistrationUrlArgsException extends PasskeyRegistrationUrlException
{

	public function __construct(
		private readonly ParseException $parseException,
	) {
		parent::__construct($parseException->getMessage(), previous: $parseException);
	}


	/**
	 * @return ParseException What was wrong with the command line, with the definition the help is drawn from
	 */
	public function getParseException(): ParseException
	{
		return $this->parseException;
	}

}
