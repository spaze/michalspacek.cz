<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration;

use MichalSpacekCz\User\SecurityActivity\SecurityEventType;
use Override;

final readonly class PasskeyResetUrl extends PasskeyRegistrationUrl
{

	#[Override]
	protected function getDestination(): string
	{
		return 'Admin:Sign:passkeyReset';
	}


	#[Override]
	protected function getInitiatedEventType(): SecurityEventType
	{
		return SecurityEventType::PasskeyResetInitiated;
	}

}
