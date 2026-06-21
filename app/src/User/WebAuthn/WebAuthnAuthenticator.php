<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyAuthenticationException;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyAuthenticationOptionsSerializationException;
use MichalSpacekCz\User\WebAuthn\Authentication\PasskeyAuthenticationResult;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationOptionsSerializationException;

interface WebAuthnAuthenticator
{

	/**
	 * @throws PasskeyRegistrationOptionsSerializationException
	 */
	public function generateRegistrationOptions(int $userId, string $username, bool $excludeExistingCredentials): string;


	/**
	 * @return string The id of the newly registered passkey credential
	 * @throws PasskeyRegistrationException
	 */
	public function verifyRegistration(string $json, string $name, int $userId): string;


	/**
	 * @throws PasskeyAuthenticationOptionsSerializationException
	 */
	public function generateAuthenticationOptions(): string;


	/**
	 * Check a passkey without remembering it as the one used to sign in. verifyAuthentication() does
	 * remember it; this is for confirming identity later without changing that.
	 *
	 * @throws PasskeyAuthenticationException
	 */
	public function verifyAssertion(string $json): PasskeyAuthenticationResult;


	/**
	 * @throws PasskeyAuthenticationException
	 */
	public function verifyAuthentication(string $json): PasskeyAuthenticationResult;

}
