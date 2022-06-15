<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Www\Presenters\BasePresenter as WwwBasePresenter;
use Nette\Security\User;
use Spaze\Session\MysqlSessionHandler;

abstract class BasePresenter extends WwwBasePresenter
{

	private MysqlSessionHandler $sessionHandler;
	private User $user;

	protected bool $haveBacklink = true;


	/**
	 * @internal
	 * @param MysqlSessionHandler $sessionHandler
	 */
	public function injectSessionHandler(MysqlSessionHandler $sessionHandler): void
	{
		$this->sessionHandler = $sessionHandler;
	}


	/**
	 * @internal
	 * @param User $user
	 */
	public function injectUser(User $user): void
	{
		$this->user = $user;
	}


	protected function startup(): void
	{
		parent::startup();
		if (!$this->user->isLoggedIn()) {
			$params = ($this->haveBacklink ? ['backlink' => $this->storeRequest()] : []);
			$this->redirect('Sign:in', $params);
		}
		$this->sessionHandler->onBeforeDataWrite[] = function () {
			$identity = $this->user->getIdentity();
			if ($identity) {
				$this->sessionHandler->setAdditionalData('key_user', $identity->getId());
			}
		};
	}

}
