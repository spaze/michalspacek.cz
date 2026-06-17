<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration;

use MichalSpacekCz\User\UserAccessRevoker;
use MichalSpacekCz\User\WebAuthn\PasskeyStorage;
use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyResetRevokeFailedException;
use Throwable;

final readonly class PasskeyResetRevoker
{

	/**
	 * @param iterable<UserAccessRevoker> $accessRevokers
	 */
	public function __construct(
		private PasskeyStorage $passkeyStorage,
		private iterable $accessRevokers,
	) {
	}


	/**
	 * Revoke every existing way into the account except the just-registered passkey.
	 *
	 * Best effort on purpose: every step runs even when an earlier one throws, so a single failure
	 * can't leave another access path alive. Any failures are collected and thrown together, so the
	 * caller can log them and tell the user the cleanup did not fully succeed.
	 *
	 * @throws PasskeyResetRevokeFailedException
	 */
	public function revoke(int $userId, string $keepCredentialId): void
	{
		$failedSteps = [];
		try {
			$this->passkeyStorage->deleteCredentialsByUserIdExcept($userId, $keepCredentialId);
		} catch (Throwable $e) {
			$failedSteps[] = $e;
		}
		foreach ($this->accessRevokers as $accessRevoker) {
			try {
				$accessRevoker->revokeForUser($userId);
			} catch (Throwable $e) {
				$failedSteps[] = $e;
			}
		}
		if ($failedSteps !== []) {
			throw new PasskeyResetRevokeFailedException($failedSteps);
		}
	}

}
