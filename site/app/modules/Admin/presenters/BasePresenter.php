<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use Spaze\Session\MysqlSessionHandler;

/**
 * Base class for all admin module presenters.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
abstract class BasePresenter extends \App\WwwModule\Presenters\BasePresenter
{

	/** @var MysqlSessionHandler */
	private $sessionHandler;

	protected $haveBacklink = true;


	/**
	 * @internal
	 * @param MysqlSessionHandler $sessionHandler
	 */
	public function injectSessionHandler(MysqlSessionHandler $sessionHandler)
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
