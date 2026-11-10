<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Pulse\Passwords;

use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\Sitemap\InSitemap;
use MichalSpacekCz\Sitemap\NotInSitemap;

final class PasswordsPresenter extends BasePresenter
{

	#[InSitemap]
	public function actionDefault(): void
	{
		$this->template->pageTitle = 'Passwords';
	}


	/**
	 * Redirect questions handler.
	 *
	 * Redirects already published URLs.
	 */
	#[NotInSitemap('redirects an old address of the questions, which are in the sitemap')]
	public function actionStoragesQuestions(): never
	{
		$this->redirectPermanent('PasswordsStorages:questions');
	}


	/**
	 * Redirect rating handler.
	 *
	 * Redirects already published URLs.
	 */
	#[NotInSitemap('redirects an old address of the rating guide, which is in the sitemap')]
	public function actionStoragesRating(): never
	{
		$this->redirectPermanent('PasswordsStorages:rating');
	}

}
