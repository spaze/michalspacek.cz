<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

/**
 * Base class for all admin module presenters.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
abstract class BasePresenter extends \App\WwwModule\Presenters\BasePresenter
{

	/** @var \MichalSpacekCz\User\Manager */
	private $authenticator;

	/**
	 * @internal
	 * @param \MichalSpacekCz\User\Manager $authenticator
	 */
	public function injectAuthenticator(\MichalSpacekCz\User\Manager $authenticator)
	{
		$this->authenticator = $authenticator;
	}


	protected function startupEx(): void
	{
		if (!$this->user->isLoggedIn()) {
			if ($this->authenticator->isReturningUser()) {
				$this->redirect('Sign:in', array('backlink' => $this->storeRequest()));
			} else {
				$this->redirect('Honeypot:signIn');
			}
		}
	}


	public function beforeRender(): void
	{
		$this->template->setTranslator($this->translator);
	}

}
