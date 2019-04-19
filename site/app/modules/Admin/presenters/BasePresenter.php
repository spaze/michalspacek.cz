<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use App\WwwModule\Presenters\BasePresenter as WwwBasePresenter;
use Spaze\Session\MysqlSessionHandler;

/**
 * Base class for all admin module presenters.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
abstract class BasePresenter extends WwwBasePresenter
{

	/** @var MysqlSessionHandler */
	private $sessionHandler;

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
		$this->template->setTranslator($this->translator);
	}

}
