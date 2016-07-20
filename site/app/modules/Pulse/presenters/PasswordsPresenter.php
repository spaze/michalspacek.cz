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

	/** @var \MichalSpacekCz\Pulse\Passwords\Rating */
	protected $passwordsRating;


	public function __construct(\MichalSpacekCz\Pulse\Passwords $passwords, \MichalSpacekCz\Pulse\Passwords\Rating $passwordsRating)
	{
		$this->passwords = $passwords;
		$this->passwordsRating = $passwordsRating;
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
		$this->template->ratingGuide = $this->passwordsRating->getRatingGuide();
	}

}
