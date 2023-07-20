<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Articles\Articles;
use MichalSpacekCz\Training\Applications\TrainingApplications;
use Nette\Application\Responses\RedirectResponse;
use Nette\Http\IResponse;

class RedirectPresenter extends BasePresenter
{

	private const GOOD_NIGHT = 5;


	public function __construct(
		private readonly TrainingApplications $trainingApplications,
		private readonly Articles $articles,
	) {
		parent::__construct();
	}


	public function actionFiles(string $token): void
	{
		$application = $this->trainingApplications->getApplicationByToken($token);
		if ($application) {
			$this->redirect(':Www:Trainings:files', $application->getTrainingAction(), $token);
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}


	public function actionApplication(string $token): void
	{
		$application = $this->trainingApplications->getApplicationByToken($token);
		if ($application) {
			$this->redirect(':Www:Trainings:application', $application->getTrainingAction(), $token);
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}


	public function actionNewestArticleByTag(string $token): void
	{
		$article = current($this->articles->getAllByTags([$token], 1));
		if ($article) {
			$this->sendResponse(new RedirectResponse($article->href, IResponse::S302_Found));
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}

}
