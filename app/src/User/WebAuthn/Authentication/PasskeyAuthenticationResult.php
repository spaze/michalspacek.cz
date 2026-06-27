<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Authentication;

final readonly class PasskeyAuthenticationResult
{

	public function __construct(
		private(set) int $userId,
		private(set) string $username,
		private(set) string $credentialId,
		private(set) string $credentialName,
	) {
	}

}
