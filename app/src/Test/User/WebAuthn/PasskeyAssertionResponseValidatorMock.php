<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\User\WebAuthn;

use MichalSpacekCz\Test\WillThrow;
use Override;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialRequestOptions;

final class PasskeyAssertionResponseValidatorMock extends AuthenticatorAssertionResponseValidator
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
		CredentialRecord $credentialRecord,
		AuthenticatorAssertionResponse $authenticatorAssertionResponse,
		PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions,
		string $host,
		?string $userHandle,
	): CredentialRecord {
		$this->maybeThrow();
		return $this->credentialRecord;
	}

}
