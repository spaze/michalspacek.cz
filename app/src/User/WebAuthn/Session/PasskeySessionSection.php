<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Session;

use MichalSpacekCz\Http\SessionSectionDeprecatedGetSet;
use Nette\Http\SessionSection;

final class PasskeySessionSection extends SessionSection
{

	use SessionSectionDeprecatedGetSet;


	private const string AUTH_CHALLENGE = 'authChallenge';
	private const string REG_CHALLENGE = 'regChallenge';
	private const string SIGNED_IN_CREDENTIAL_ID = 'credentialId';


	public function removeAll(): void
	{
		parent::remove();
	}


	public function setAuthChallenge(string $challenge): void
	{
		parent::set(self::AUTH_CHALLENGE, $challenge);
	}


	public function getRemoveAuthChallenge(): ?string
	{
		$challenge = parent::get(self::AUTH_CHALLENGE);
		parent::remove(self::AUTH_CHALLENGE);
		return is_string($challenge) ? $challenge : null;
	}


	public function setRegChallenge(string $challenge): void
	{
		parent::set(self::REG_CHALLENGE, $challenge);
	}


	public function getRemoveRegChallenge(): ?string
	{
		$challenge = parent::get(self::REG_CHALLENGE);
		parent::remove(self::REG_CHALLENGE);
		return is_string($challenge) ? $challenge : null;
	}


	public function setSignedInCredentialId(string $id): void
	{
		parent::set(self::SIGNED_IN_CREDENTIAL_ID, $id);
	}


	public function getSignedInCredentialId(): ?string
	{
		$id = parent::get(self::SIGNED_IN_CREDENTIAL_ID);
		return is_string($id) ? $id : null;
	}


	public function removeSignedInCredentialId(): void
	{
		parent::remove(self::SIGNED_IN_CREDENTIAL_ID);
	}

}
