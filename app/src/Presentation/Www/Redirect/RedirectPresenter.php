<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Redirect;

use MichalSpacekCz\Articles\Articles;
use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\Sitemap\NotInSitemap;
use MichalSpacekCz\Training\Applications\TrainingApplications;
use MichalSpacekCz\Utils\Sleep;
use Nette\Application\Responses\RedirectResponse;
use Nette\Http\IResponse;

final class RedirectPresenter extends BasePresenter
{

	private const int GOOD_NIGHT = 5;

	private const int GOOD_MORNING = 8;


	public function __construct(
		private readonly TrainingApplications $trainingApplications,
		private readonly Articles $articles,
		private readonly Sleep $sleep,
	) {
		parent::__construct();
	}


	#[NotInSitemap('redirects an attendee with an access token to their materials')]
	public function actionFiles(string $token): void
	{
		$application = $this->trainingApplications->getApplicationByToken($token);
		if ($application !== null) {
			$this->redirect(':Www:Trainings:files', $application->getTrainingAction(), $token);
		} else {
			$this->sleep->randomSleep(self::GOOD_NIGHT, self::GOOD_MORNING);
		}
	}


	#[NotInSitemap('redirects an applicant with an access token to their application')]
	public function actionApplication(string $token): void
	{
		$application = $this->trainingApplications->getApplicationByToken($token);
		if ($application !== null) {
			$this->redirect(':Www:Trainings:application', $application->getTrainingAction(), $token);
		} else {
			$this->sleep->randomSleep(self::GOOD_NIGHT, self::GOOD_MORNING);
		}
	}


	#[NotInSitemap('redirects to the newest article with the tag, the article is in the sitemap')]
	public function actionNewestArticleByTag(string $token): void
	{
		$article = current($this->articles->getAllByTags([$token], 1));
		if ($article !== false) {
			$this->sendResponse(new RedirectResponse($article->getHref(), IResponse::S302_Found));
		} else {
			$this->sleep->randomSleep(self::GOOD_NIGHT, self::GOOD_MORNING);
		}
	}

}
