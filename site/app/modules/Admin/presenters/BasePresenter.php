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

	protected function startup(): void
	{
		parent::startup();
		if (!$this->user->isLoggedIn()) {
			$params = ($this->authenticator->isReturningUser() ? ['backlink' => $this->storeRequest()] : []);
			$this->redirect('Sign:in', $params);
		}
	}


	public function beforeRender(): void
	{
		$this->template->setTranslator($this->translator);
	}

}
