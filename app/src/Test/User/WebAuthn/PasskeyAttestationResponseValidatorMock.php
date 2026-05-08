<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\User\WebAuthn;

use MichalSpacekCz\Test\WillThrow;
use Override;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialCreationOptions;

final class PasskeyAttestationResponseValidatorMock extends AuthenticatorAttestationResponseValidator
{

	use WillThrow;


	/**
	 * @noinspection PhpMissingParentConstructorInspection Intentionally
	 * @phpstan-ignore constructor.missingParentCall
	 */
	public function __construct(private readonly CredentialRecord $credentialRecord)
	{
	}


	#[Override]
	public function check(
		AuthenticatorAttestationResponse $authenticatorAttestationResponse,
		PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions,
		string $host,
	): CredentialRecord {
		$this->maybeThrow();
		return $this->credentialRecord;
	}

}
