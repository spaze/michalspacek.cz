<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Authentication;

/**
 * A presenter that can send the user to confirm their identity with a passkey. Lets
 * {@see Reauthentication::requireFreshAuth()} trigger that without knowing how the redirect is done.
 */
interface ReauthenticationRedirector
{

	public function redirectToReauthentication(): never;

}
