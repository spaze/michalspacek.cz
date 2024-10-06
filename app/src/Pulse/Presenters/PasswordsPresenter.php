<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Presenters;

use MichalSpacekCz\Www\Presenters\BasePresenter;

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
	public function actionStoragesQuestions(): never
	{
		$this->redirectPermanent('PasswordsStorages:questions');
	}


	/**
	 * Redirect rating handler.
	 *
	 * Redirects already published URLs.
	 */
	public function actionStoragesRating(): never
	{
		$this->redirectPermanent('PasswordsStorages:rating');
	}

}
