<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\User\AuthTokens\UserAuthToken;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationDisabledException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationInvalidOrExpiredTokenException;
use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyRegistrationUserMismatchException;
use Nette\Security\User;

final readonly class PasskeyRegistration
{

	public function __construct(
		private PasskeyRegistrationTokens $registrationTokens,
		private WebAuthnAuthenticator $passkeyAuthenticator,
		private User $user,
	) {
	}


	public function isEnabled(): bool
	{
		return $this->registrationTokens->isEnabled();
	}


	/**
	 * @throws PasskeyRegistrationDisabledException
	 * @throws PasskeyRegistrationInvalidOrExpiredTokenException
	 * @throws PasskeyRegistrationUserMismatchException
	 */
	public function getUserAuthToken(string $token): UserAuthToken
	{
		$userAuthToken = $this->registrationTokens->verify($token);
		if ($userAuthToken === null) {
			throw new PasskeyRegistrationInvalidOrExpiredTokenException();
		}
		// When signed in, the token must be the signed-in user's, so a leaked link can't register a
		// passkey onto someone else's account; logged out (reset recovery) there is no one to match.
		$loggedInUserId = $this->user->isLoggedIn() ? (int) $this->user->getId() : null;
		if ($loggedInUserId !== null && $userAuthToken->getUserId() !== $loggedInUserId) {
			throw new PasskeyRegistrationUserMismatchException();
		}
		return $userAuthToken;
	}


	/**
	 * @throws PasskeyRegistrationDisabledException
	 * @throws PasskeyRegistrationInvalidOrExpiredTokenException
	 * @throws PasskeyRegistrationUserMismatchException
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
