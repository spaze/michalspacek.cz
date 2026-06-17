<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration;

use MichalSpacekCz\User\WebAuthn\Registration\Exceptions\PasskeyResetRevokeFailedException;

final readonly class PasskeyRegistrationResult
{

	public function __construct(
		public string $username,
		public int $userId,
		public string $keepCredentialId,
		public ?PasskeyResetRevokeFailedException $revokeFailure = null,
	) {
	}


	public function withRevokeFailure(PasskeyResetRevokeFailedException $revokeFailure): self
	{
		return new self($this->username, $this->userId, $this->keepCredentialId, $revokeFailure);
	}

}
