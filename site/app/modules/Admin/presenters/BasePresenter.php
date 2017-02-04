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

	protected function startupEx(): void
	{
		$authenticator = $this->getContext()->getByType(\MichalSpacekCz\User\Manager::class);
		if (!$this->user->isLoggedIn()) {
			if ($authenticator->isReturningUser()) {
				$this->redirect('Sign:in', array('backlink' => $this->storeRequest()));
			} else {
				$this->redirect('Honeypot:signIn');
			}
		}
	}


	public function beforeRender(): void
	{
		$this->template->trackingCode = false;
		$this->template->setTranslator($this->translator);
	}

}
