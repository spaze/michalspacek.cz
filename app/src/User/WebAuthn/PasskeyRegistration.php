<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\User\AuthTokens\UserAuthToken;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationDisabledException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationInvalidOrExpiredTokenException;

final readonly class PasskeyRegistration
{

	public function __construct(
		private PasskeyRegistrationTokens $registrationTokens,
		private WebAuthnAuthenticator $passkeyAuthenticator,
	) {
	}


	/**
	 * @throws PasskeyRegistrationDisabledException
	 * @throws PasskeyRegistrationInvalidOrExpiredTokenException
	 */
	public function getUserAuthToken(string $token): UserAuthToken
	{
		$userAuthToken = $this->registrationTokens->verify($token);
		if ($userAuthToken === null) {
			throw new PasskeyRegistrationInvalidOrExpiredTokenException();
		}
		return $userAuthToken;
	}


	/**
	 * @throws PasskeyRegistrationDisabledException
	 * @throws PasskeyRegistrationInvalidOrExpiredTokenException
	 */
	public function generateRegistrationOptions(string $token): string
	{
		$userAuthToken = $this->getUserAuthToken($token);
		return $this->passkeyAuthenticator->generateRegistrationOptions(
			$userAuthToken->getUserId(),
			$userAuthToken->getUsername(),
		);
	}


	public function cleanupToken(UserAuthToken $userAuthToken): void
	{
		$this->registrationTokens->deleteById($userAuthToken->getId());
	}

}
