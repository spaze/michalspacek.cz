<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\UserAuthToken;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetDisabledException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetInvalidOrExpiredTokenException;

final readonly class PasskeyReset
{

	public function __construct(
		private Manager $authenticator,
		private WebAuthnAuthenticator $passkeyAuthenticator,
	) {
	}


	/**
	 * @throws PasskeyResetDisabledException
	 * @throws PasskeyResetInvalidOrExpiredTokenException
	 */
	public function getUserAuthToken(string $token): UserAuthToken
	{
		$userAuthToken = $this->authenticator->verifyPasskeyResetToken($token);
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
		$this->authenticator->deletePasskeyResetToken($userAuthToken->getId());
	}

}
