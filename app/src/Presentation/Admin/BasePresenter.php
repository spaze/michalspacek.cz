<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin;

use MichalSpacekCz\Http\Robots\Robots;
use MichalSpacekCz\Http\Robots\RobotsRule;
use MichalSpacekCz\Presentation\Www\BasePresenter as WwwBasePresenter;
use MichalSpacekCz\User\WebAuthn\Authentication\Reauthentication;
use MichalSpacekCz\User\WebAuthn\Authentication\ReauthenticationRedirector;
use Nette\Security\User;
use Override;

abstract class BasePresenter extends WwwBasePresenter implements ReauthenticationRedirector
{

	private User $user;
	private Robots $robots;
	private Reauthentication $reauthentication;

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


	/**
	 * @internal
	 */
	public function injectReauthentication(Reauthentication $reauthentication): void
	{
		$this->reauthentication = $reauthentication;
	}


	/**
	 * Call at the top of an action whose page should be seen only by someone who confirmed their identity
	 * with a passkey recently, such as the phpinfo() output. If they haven't, it sends them to the reauth
	 * page and brings them back. The reauth page must not call this or it would keep sending the user to
	 * itself. To confirm before a form changes something sensitive, confirm in place with
	 * PasskeyAuthenticationControls on the form instead of gating the whole page.
	 */
	protected function requireReauthentication(): void
	{
		$this->reauthentication->requireFreshAuth($this);
	}


	#[Override]
	public function redirectToReauthentication(): never
	{
		$this->redirect(':Admin:Reauth:', ['backlink' => $this->storeRequest()]);
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
