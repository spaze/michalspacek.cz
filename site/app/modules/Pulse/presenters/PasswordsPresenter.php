<?php
namespace App\PulseModule\Presenters;

/**
 * Pulse presenter.
 *
 * @author Michal Špaček
 * @package pulse.michalspacek.cz
 */
class PasswordsPresenter extends \App\WwwModule\Presenters\BasePresenter
{

	/**
	 * Default action handler.
	 */
	public function actionDefault($param)
	{
		$this->template->pageTitle = 'Passwords';
	}


	/**
	 * Redirect questions handler.
	 *
	 * Redirects already published URLs.
	 */
	public function actionStoragesQuestions()
	{
		$this->redirect(\Nette\Http\IResponse::S301_MOVED_PERMANENTLY, 'PasswordsStorages:questions');
	}


	/**
	 * Redirect rating handler.
	 *
	 * Redirects already published URLs.
	 */
	public function actionStoragesRating()
	{
		$this->redirect(\Nette\Http\IResponse::S301_MOVED_PERMANENTLY, 'PasswordsStorages:rating');
	}

}
