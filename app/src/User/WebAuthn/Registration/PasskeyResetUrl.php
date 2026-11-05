<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration;

use MichalSpacekCz\User\SecurityActivity\SecurityEventType;
use Override;

final readonly class PasskeyResetUrl extends PasskeyRegistrationUrl
{

	#[Override]
	public static function getDescription(): string
	{
		return "Generates a recovery link that registers a new passkey and revokes the user's other passkeys, tokens and sessions. To add a passkey without revoking anything, use `passkey-add.php`";
	}


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
