<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration;

use MichalSpacekCz\User\AuthTokens\UserAuthToken;
use MichalSpacekCz\User\Notifications\UserSecurityNotifier;
use MichalSpacekCz\User\SecurityActivity\SecurityEventLogger;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationDisabledException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationInvalidOrExpiredTokenException;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyRegistrationUserMismatchException;
use MichalSpacekCz\User\WebAuthn\WebAuthnAuthenticator;
use Nette\Security\User;

abstract readonly class PasskeyRegistration
{

	/**
	 * Whether to exclude the user's existing passkeys from the registration options, so an
	 * authenticator that already holds one for this app won't offer to register another. Add
	 * excludes them (a second passkey on the same device is pointless); reset allows re-enrolling.
	 */
	abstract protected function excludeExistingCredentials(): bool;


	public function __construct(
		private PasskeyRegistrationTokens $registrationTokens,
		private WebAuthnAuthenticator $passkeyAuthenticator,
		private User $user,
		protected UserSecurityNotifier $notifier,
		protected SecurityEventLogger $securityEventLogger,
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
	public function generateRegistrationOptions(string $token): string
	{
		$userAuthToken = $this->getUserAuthToken($token);
		return $this->passkeyAuthenticator->generateRegistrationOptions(
			$userAuthToken->getUserId(),
			$userAuthToken->getUsername(),
			$this->excludeExistingCredentials(),
		);
	}


	/**
	 * Verify the submitted credential and save the passkey, returning what was registered. The token
	 * is consumed first, so the same link can't be replayed.
	 *
	 * @throws PasskeyRegistrationDisabledException
	 * @throws PasskeyRegistrationInvalidOrExpiredTokenException
	 * @throws PasskeyRegistrationUserMismatchException
	 */
	public function register(string $credentialJson, string $name, string $token): PasskeyRegistrationResult
	{
		$userAuthToken = $this->getUserAuthToken($token);
		$this->cleanupToken($userAuthToken);
		$keepCredentialId = $this->passkeyAuthenticator->verifyRegistration($credentialJson, $name, $userAuthToken->getUserId());
		return new PasskeyRegistrationResult($userAuthToken->getUsername(), $userAuthToken->getUserId(), $keepCredentialId);
	}


	/**
	 * @throws PasskeyRegistrationDisabledException
	 * @throws PasskeyRegistrationInvalidOrExpiredTokenException
	 * @throws PasskeyRegistrationUserMismatchException
	 */
	private function getUserAuthToken(string $token): UserAuthToken
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


	private function cleanupToken(UserAuthToken $userAuthToken): void
	{
		$this->registrationTokens->deleteById($userAuthToken->getId());
	}

}
