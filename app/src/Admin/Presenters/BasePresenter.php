<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Http\Robots\Robots;
use MichalSpacekCz\Http\Robots\RobotsRule;
use MichalSpacekCz\Www\Presenters\BasePresenter as WwwBasePresenter;
use Nette\Security\User;
use Override;
use Spaze\Session\MysqlSessionHandler;

abstract class BasePresenter extends WwwBasePresenter
{

	private MysqlSessionHandler $sessionHandler;
	private User $user;
	private Robots $robots;

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


	/**
	 * @internal
	 */
	public function injectRobots(Robots $robots): void
	{
		$this->robots = $robots;
	}


	#[Override]
	protected function startup(): void
	{
		parent::startup();
		$this->robots->setHeader([RobotsRule::NoIndex, RobotsRule::NoFollow]);
		if (!$this->user->isLoggedIn()) {
			$params = ($this->haveBacklink ? ['backlink' => $this->storeRequest()] : []);
			$this->redirect(':Admin:Sign:in', $params);
		}
		$this->sessionHandler->onBeforeDataWrite[] = function (): void {
			$identity = $this->user->getIdentity();
			if ($identity) {
				$this->sessionHandler->setAdditionalData('key_user', $identity->getId());
			}
		};
	}

}
