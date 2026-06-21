<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\User\WebAuthn;

use MichalSpacekCz\User\WebAuthn\Authentication\ReauthenticationRedirector;
use Override;
use RuntimeException;

/**
 * Records that a redirect to the reauth page was asked for. Throws so the never-returning redirect
 * doesn't fall through, the way the real presenter aborts the request.
 */
final class ReauthenticationRedirectorMock implements ReauthenticationRedirector
{

	public bool $redirected = false;


	#[Override]
	public function redirectToReauthentication(): never
	{
		$this->redirected = true;
		throw new RuntimeException('Redirected to reauthentication');
	}

}
