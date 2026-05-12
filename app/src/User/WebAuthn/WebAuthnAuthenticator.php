<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyAuthenticationException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyAuthenticationOptionsSerializationException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationOptionsSerializationException;

interface WebAuthnAuthenticator
{

	/**
	 * @throws PasskeyRegistrationOptionsSerializationException
	 */
	public function generateRegistrationOptions(int $userId, string $username): string;


	/**
	 * @throws PasskeyRegistrationException
	 */
	public function verifyRegistration(string $json, string $name, int $userId): void;


	/**
	 * @throws PasskeyAuthenticationOptionsSerializationException
	 */
	public function generateAuthenticationOptions(): string;


	/**
	 * @throws PasskeyAuthenticationException
	 */
	public function verifyAuthentication(string $json): PasskeyAuthenticationResult;

}
