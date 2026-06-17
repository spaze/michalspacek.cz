<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\User\WebAuthn;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\WillThrow;
use MichalSpacekCz\User\WebAuthn\PasskeyAuthenticationResult;
use MichalSpacekCz\User\WebAuthn\WebAuthnAuthenticator;
use Override;

final class PasskeyAuthenticatorMock implements WebAuthnAuthenticator
{

	use WillThrow;


	private ?PasskeyAuthenticationResult $authenticationResult = null;

	public ?bool $lastExcludeExistingCredentials = null;


	public function setAuthenticationResult(PasskeyAuthenticationResult $result): void
	{
		$this->authenticationResult = $result;
	}


	#[Override]
	public function generateAuthenticationOptions(): string
	{
		return '{}';
	}


	#[Override]
	public function generateRegistrationOptions(int $userId, string $username, bool $excludeExistingCredentials): string
	{
		$this->lastExcludeExistingCredentials = $excludeExistingCredentials;
		return '{}';
	}


	#[Override]
	public function verifyAuthentication(string $json): PasskeyAuthenticationResult
	{
		$this->maybeThrow();
		if ($this->authenticationResult === null) {
			throw new ShouldNotHappenException();
		}
		return $this->authenticationResult;
	}


	#[Override]
	public function verifyRegistration(string $json, string $name, int $userId): string
	{
		$this->maybeThrow();
		return 'mockPasskeyCredentialId';
	}

}
