<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User;

use Nette\Security\User;
use Spaze\Session\MysqlSessionHandler;

final readonly class UserSessionAdditionalData
{

	public function __construct(
		private User $user,
		private MysqlSessionHandler $sessionHandler,
	) {
	}


	public function init(): void
	{
		$identity = $this->user->getIdentity();
		if ($identity !== null) {
			$this->sessionHandler->setAdditionalData('key_user', $identity->getId());
		}
		$this->user->onLoggedIn[] = function (User $user): void {
			$identity = $user->getIdentity();
			if ($identity !== null) {
				$this->sessionHandler->setAdditionalData('key_user', $identity->getId());
			}
		};
		$this->user->onLoggedOut[] = function (): void {
			$this->sessionHandler->setAdditionalData('key_user', null);
		};
	}

}
