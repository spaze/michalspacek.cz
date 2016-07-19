<?php
namespace App\PulseModule\Presenters;

/**
 * Homepage presenter.
 *
 * @author Michal Špaček
 * @package pulse.michalspacek.cz
 */
class PasswordsPresenter extends \App\Presenters\BasePresenter
{

	/** @var \MichalSpacekCz\Pulse\Passwords */
	protected $passwords;


	public function __construct(\MichalSpacekCz\Pulse\Passwords $passwords)
	{
		$this->passwords = $passwords;
	}


	/**
	 * Storages action handler.
	 */
	public function actionStorages($param = null)
	{
		if ($param === null) {
			$data = $this->passwords->getAllStorages();
		} else {
			$data = $this->passwords->getStorages($param);
		}
		$this->template->data = $data;
	}

}
