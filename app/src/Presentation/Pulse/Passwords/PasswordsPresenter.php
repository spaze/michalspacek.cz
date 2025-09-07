<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Pulse\Passwords;

final class PasswordsPresenter extends \MichalSpacekCz\Presentation\Www\BasePresenter
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
