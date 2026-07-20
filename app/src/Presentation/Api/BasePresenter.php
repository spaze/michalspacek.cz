<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Api;

use MichalSpacekCz\Api\Endpoint\EndpointAccess;
use MichalSpacekCz\Api\Endpoint\EndpointAccessAttribute;
use MichalSpacekCz\Http\Robots\Robots;
use MichalSpacekCz\Http\Robots\RobotsRule;
use MichalSpacekCz\Http\SecurityHeaders\CrossOriginResourceSharing;
use MichalSpacekCz\Presentation\Www\BasePresenter as WwwBasePresenter;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;
use Override;

abstract class BasePresenter extends WwwBasePresenter
{

	private Robots $robots;
	private CrossOriginResourceSharing $crossOriginResourceSharing;


	/**
	 * @internal
	 */
	public function injectRobots(Robots $robots): void
	{
		$this->robots = $robots;
	}


	/**
	 * @internal
	 */
	public function injectCrossOriginResourceSharing(CrossOriginResourceSharing $crossOriginResourceSharing): void
	{
		$this->crossOriginResourceSharing = $crossOriginResourceSharing;
	}


	#[Override]
	final protected function startup(): void
	{
		parent::startup();
		$this->robots->setHeader([RobotsRule::NoIndex, RobotsRule::NoFollow]);
		$this->crossOriginResourceSharing->accessControlAllowOrigin('Www:Homepage:');
		$this->checkAccessAttribute();
		$this->startupApi();
	}


	/**
	 * Runs after the access check; override this instead of startup().
	 */
	protected function startupApi(): void
	{
	}


	/**
	 * Require exactly one access attribute (one implementing EndpointAccessAttribute), so a newly added
	 * presenter is closed by default (none declared) and a contradictory declaration (more than one) is rejected.
	 *
	 * @throws BadRequestException
	 */
	private function checkAccessAttribute(): void
	{
		if (!EndpointAccess::isDeclared($this)) {
			throw new BadRequestException(sprintf('%s must declare exactly one access attribute implementing %s', $this::class, EndpointAccessAttribute::class), IResponse::S403_Forbidden);
		}
	}

}
