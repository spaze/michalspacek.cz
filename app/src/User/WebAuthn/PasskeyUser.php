<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn;

final readonly class PasskeyUser
{

	public function __construct(
		private(set) int $id,
		private(set) string $username,
		private(set) string $credentialName,
	) {
	}

}
