<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\User\AuthTokens\UserAuthToken;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetDisabledException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetInvalidOrExpiredTokenException;

final readonly class PasskeyReset
{

	public function __construct(
		private PasskeyRegistrationTokens $registrationTokens,
		private WebAuthnAuthenticator $passkeyAuthenticator,
	) {
	}


	/**
	 * @throws PasskeyResetDisabledException
	 * @throws PasskeyResetInvalidOrExpiredTokenException
	 */
	public function getUserAuthToken(string $token): UserAuthToken
	{
		$userAuthToken = $this->registrationTokens->verify($token);
		if ($userAuthToken === null) {
			throw new PasskeyResetInvalidOrExpiredTokenException();
		}
		return $userAuthToken;
	}


	/**
	 * @throws PasskeyResetDisabledException
	 * @throws PasskeyResetInvalidOrExpiredTokenException
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
