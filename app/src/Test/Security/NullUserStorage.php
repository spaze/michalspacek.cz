<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Security;

use Nette\Security\IIdentity;
use Nette\Security\User;
use Nette\Security\UserStorage;
use Override;

final class NullUserStorage implements UserStorage
{

	private bool $authenticated = false;
	private ?IIdentity $identity = null;
	private ?int $reason = null;


	#[Override]
	public function saveAuthentication(IIdentity $identity): void
	{
		$this->authenticated = true;
		$this->identity = $identity;
	}


	#[Override]
	public function clearAuthentication(bool $clearIdentity): void
	{
		$this->authenticated = true;
		$this->reason = User::LogoutManual;
		if ($clearIdentity === true) {
			$this->identity = null;
		}
	}


	#[Override]
	public function getState(): array
	{
		return [$this->authenticated, $this->identity, $this->reason];
	}


	#[Override]
	public function setExpiration(?string $expire, bool $clearIdentity): void
	{
	}

}
