<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Api\Presenters;

use MichalSpacekCz\Http\Robots\Robots;
use MichalSpacekCz\Http\Robots\RobotsRule;
use MichalSpacekCz\Www\Presenters\BasePresenter as WwwBasePresenter;
use Override;

abstract class BasePresenter extends WwwBasePresenter
{

	private Robots $robots;


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
	}

}
