<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration;

use MichalSpacekCz\User\SecurityActivity\SecurityEventType;
use Override;

final readonly class PasskeyAddUrl extends PasskeyRegistrationUrl
{

	#[Override]
	protected function getDestination(): string
	{
		return 'Admin:Passkeys:add';
	}


	#[Override]
	protected function getInitiatedEventType(): SecurityEventType
	{
		return SecurityEventType::PasskeyAddInitiated;
	}

}
