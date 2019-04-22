<?php
declare(strict_types = 1);

namespace App\PulseModule\Presenters;

use App\WwwModule\Presenters\BasePresenter;

class PasswordsPresenter extends BasePresenter
{

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
		$this->redirectPermanent('PasswordsStorages:questions');
	}


	/**
	 * Redirect rating handler.
	 *
	 * Redirects already published URLs.
	 */
	public function actionStoragesRating(): void
	{
		$this->redirectPermanent('PasswordsStorages:rating');
	}

}
