<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Authentication;

use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\User\Exceptions\IdentityIdNotIntException;
use MichalSpacekCz\User\Manager;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyAuthenticationException;
use MichalSpacekCz\User\WebAuthn\Authentication\Exceptions\PasskeyReauthenticationUserMismatchException;
use MichalSpacekCz\User\WebAuthn\Session\PasskeySessionSection;
use MichalSpacekCz\User\WebAuthn\WebAuthnAuthenticator;
use Nette\Security\User;

/**
 * Tracks whether the user confirmed their identity with a passkey recently enough (within {@see $ttl})
 * to be allowed to view a sensitive page, such as the phpinfo() output. Only an actual passkey sign-in
 * or a confirmation here counts; being logged in by itself does not.
 */
final readonly class Reauthentication
{

	public function __construct(
		private WebAuthnAuthenticator $passkeyAuthenticator,
		private PasskeySessionSection $passkeySessionSection,
		private DateTimeFactory $dateTimeFactory,
		private User $user,
		private Manager $manager,
		private string $ttl,
	) {
	}


	public function getTtl(): string
	{
		return $this->ttl;
	}


	public function isFreshAuth(): bool
	{
		$reauthAt = $this->passkeySessionSection->getReauthAt();
		if ($reauthAt === null) {
			return false;
		}
		return $reauthAt >= $this->dateTimeFactory->create('-' . $this->ttl)->getTimestamp();
	}


	public function recordFreshAuth(): void
	{
		$this->passkeySessionSection->setReauthAt($this->dateTimeFactory->create()->getTimestamp());
	}


	/**
	 * Let viewing a sensitive page proceed only if the user confirmed their identity recently; otherwise
	 * send them to the reauth page to confirm now. For a form that changes something sensitive, confirm
	 * in place with {@see \MichalSpacekCz\Form\Controls\PasskeyAuthenticationControls} instead of gating the page.
	 */
	public function requireFreshAuth(ReauthenticationRedirector $redirector): void
	{
		if ($this->isFreshAuth()) {
			return;
		}
		$redirector->redirectToReauthentication();
	}


	/**
	 * Check the passkey is the current user's own and return its name; rejects someone else's so it can't
	 * confirm identity for this session. Deliberately does not refresh the freshness window itself: the
	 * caller does that only when the guarded submit succeeds, so a submit that then fails leaves no fresh
	 * window behind.
	 *
	 * @throws PasskeyAuthenticationException
	 * @throws PasskeyReauthenticationUserMismatchException
	 * @throws IdentityIdNotIntException
	 */
	public function verify(string $credentialJson): string
	{
		$result = $this->passkeyAuthenticator->verifyAssertion($credentialJson);
		$signedInUserId = $this->manager->getUserId($this->user);
		if ($result->userId !== $signedInUserId) {
			throw new PasskeyReauthenticationUserMismatchException($signedInUserId, $result->userId);
		}
		return $result->credentialName;
	}

}
