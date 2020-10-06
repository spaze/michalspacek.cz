<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use App\WwwModule\Presenters\BasePresenter as WwwBasePresenter;
use Spaze\Session\MysqlSessionHandler;

abstract class BasePresenter extends WwwBasePresenter
{

	/** @var MysqlSessionHandler */
	private $sessionHandler;

	/** @var boolean */
	protected $haveBacklink = true;


	/**
	 * @internal
	 * @param MysqlSessionHandler $sessionHandler
	 */
	public function injectSessionHandler(MysqlSessionHandler $sessionHandler): void
	{
		$this->sessionHandler = $sessionHandler;
	}


	protected function startup(): void
	{
		parent::startup();
		if (!$this->user->isLoggedIn()) {
			$params = ($this->haveBacklink ? ['backlink' => $this->storeRequest()] : []);
			$this->redirect('Sign:in', $params);
		} elseif ($this->user->getIdentity()) {
			$this->sessionHandler->onBeforeDataWrite[] = function () {
				$this->sessionHandler->setAdditionalData('key_user', $this->user->getIdentity()->getId());
			};
		}
	}


	public function beforeRender(): void
	{
		$this->template->darkMode = $this->theme->isDarkMode();
		$this->template->setTranslator($this->translator);
	}

}
