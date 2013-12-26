<?php
namespace AdminModule;

/**
 * Base class for all admin module presenters.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
abstract class BasePresenter extends \BasePresenter
{


	protected function startup()
	{
		parent::startup();
		if (!$this->user->isLoggedIn()) {
			$this->authenticator->verifySignInAuthorization($this->getSession('admin')->knockKnock);
			$this->redirect('Sign:in');
		}
	}


	public function beforeRender()
	{
		$this->template->trackingCode = false;
	}


}
