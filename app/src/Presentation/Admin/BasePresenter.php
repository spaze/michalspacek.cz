<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin;

use MichalSpacekCz\Http\Robots\Robots;
use MichalSpacekCz\Http\Robots\RobotsRule;
use MichalSpacekCz\Presentation\Www\BasePresenter as WwwBasePresenter;
use Nette\Security\User;
use Override;

abstract class BasePresenter extends WwwBasePresenter
{

	private User $user;
	private Robots $robots;

	protected bool $haveBacklink = true;


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
	}

}
