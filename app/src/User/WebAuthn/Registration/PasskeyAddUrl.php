<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration;

use MichalSpacekCz\User\SecurityActivity\SecurityEventType;
use Override;

final readonly class PasskeyAddUrl extends PasskeyRegistrationUrl
{

	#[Override]
	public static function getDescription(): string
	{
		return "Generates a one-time link that adds a new passkey to the user's account, to be opened while signed in as that user";
	}


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
