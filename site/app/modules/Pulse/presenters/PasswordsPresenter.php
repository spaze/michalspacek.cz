<?php
declare(strict_types = 1);

namespace App\PulseModule\Presenters;

use App\WwwModule\Presenters\BasePresenter;
use Nette\Http\IResponse;

/**
 * Pulse presenter.
 *
 * @author Michal Špaček
 * @package pulse.michalspacek.cz
 */
class PasswordsPresenter extends BasePresenter
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
		$this->redirect(IResponse::S301_MOVED_PERMANENTLY, 'PasswordsStorages:questions');
	}


	/**
	 * Redirect rating handler.
	 *
	 * Redirects already published URLs.
	 */
	public function actionStoragesRating(): void
	{
		$this->redirect(IResponse::S301_MOVED_PERMANENTLY, 'PasswordsStorages:rating');
	}

}
