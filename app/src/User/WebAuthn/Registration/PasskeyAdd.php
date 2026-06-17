<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration;

use Override;

final readonly class PasskeyAdd extends PasskeyRegistration
{

	#[Override]
	protected function excludeExistingCredentials(): bool
	{
		return true;
	}

}
