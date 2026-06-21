<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Authentication;

use MichalSpacekCz\DateTime\DateTimeFactory;
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
		private string $ttl,
	) {
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
	 * Check the passkey the user just used and, only if it is their own, mark their identity as
	 * confirmed. This stops someone else's passkey from confirming identity for this session.
	 *
	 * @throws PasskeyAuthenticationException
	 * @throws PasskeyReauthenticationUserMismatchException
	 */
	public function verify(string $credentialJson): void
	{
		$result = $this->passkeyAuthenticator->verifyAssertion($credentialJson);
		$signedInUserId = (int)$this->user->getId();
		if ($result->userId !== $signedInUserId) {
			throw new PasskeyReauthenticationUserMismatchException($signedInUserId, $result->userId);
		}
		$this->recordFreshAuth();
	}

}
