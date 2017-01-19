<?php
declare(strict_types = 1);

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
	public function actionDefault(): void
	{
		$this->template->pageTitle = 'Passwords';
	}


	/**
	 * Redirect questions handler.
	 *
	 * Redirects already published URLs.
	 */
	public function actionStoragesQuestions(): void
	{
		$this->redirect(\Nette\Http\IResponse::S301_MOVED_PERMANENTLY, 'PasswordsStorages:questions');
	}


	/**
	 * Redirect rating handler.
	 *
	 * Redirects already published URLs.
	 */
	public function actionStoragesRating(): void
	{
		$this->redirect(\Nette\Http\IResponse::S301_MOVED_PERMANENTLY, 'PasswordsStorages:rating');
	}

}
