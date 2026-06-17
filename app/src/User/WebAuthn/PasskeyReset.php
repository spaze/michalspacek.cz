<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

use MichalSpacekCz\User\WebAuthn\Exceptions\PasskeyResetRevokeFailedException;
use Nette\Security\User;
use Override;

final readonly class PasskeyReset extends PasskeyRegistration
{

	public function __construct(
		PasskeyRegistrationTokens $registrationTokens,
		WebAuthnAuthenticator $passkeyAuthenticator,
		User $user,
		private PasskeyResetRevoker $revoker,
	) {
		parent::__construct($registrationTokens, $passkeyAuthenticator, $user);
	}


	#[Override]
	protected function excludeExistingCredentials(): bool
	{
		return false;
	}


	/**
	 * Register the new passkey, then revoke every other way into the account. The passkey is saved
	 * before the revoke runs, so a revoke failure still leaves the user a working passkey; it is
	 * reported on the result rather than thrown, because the registration itself succeeded.
	 */
	#[Override]
	public function register(string $credentialJson, string $name, string $token): PasskeyRegistrationResult
	{
		$result = parent::register($credentialJson, $name, $token);
		try {
			$this->revoker->revoke($result->userId, $result->keepCredentialId);
			return $result;
		} catch (PasskeyResetRevokeFailedException $e) {
			return $result->withRevokeFailure($e);
		}
	}

}
