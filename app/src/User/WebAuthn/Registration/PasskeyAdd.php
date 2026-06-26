<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Registration;

use MichalSpacekCz\User\SecurityActivity\SecurityEventType;
use Override;

final readonly class PasskeyAdd extends PasskeyRegistration
{

	#[Override]
	protected function excludeExistingCredentials(): bool
	{
		return true;
	}


	#[Override]
	public function register(string $credentialJson, string $name, string $token): PasskeyRegistrationResult
	{
		$result = parent::register($credentialJson, $name, $token);
		$this->notifier->passkeyAdded($result->userId, $name);
		$this->securityEventLogger->record($result->userId, SecurityEventType::PasskeyAddFinished, ['name' => $name]);
		return $result;
	}

}
