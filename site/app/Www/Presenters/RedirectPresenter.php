<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Articles;
use MichalSpacekCz\Training\Applications;
use Nette\Application\Responses\RedirectResponse;
use Nette\Http\IResponse;

class RedirectPresenter extends BasePresenter
{

	private const GOOD_NIGHT = 5;

	/** @var Applications */
	protected $trainingApplications;

	/** @var Articles */
	protected $articles;


	public function __construct(Applications $trainingApplications, Articles $articles)
	{
		$this->trainingApplications = $trainingApplications;
		$this->articles = $articles;
		parent::__construct();
	}


	public function actionFiles(string $token): void
	{
		$application = $this->trainingApplications->getApplicationByToken($token);
		if ($application) {
			$this->redirect(':Www:Trainings:files', $application->trainingAction, $token);
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}


	public function actionApplication(string $token): void
	{
		$application = $this->trainingApplications->getApplicationByToken($token);
		if ($application) {
			$this->redirect(':Www:Trainings:application', $application->trainingAction, $token);
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}


	public function actionNewestArticleByTag(string $token): void
	{
		$article = current($this->articles->getAllByTags([$token], 1));
		if ($article) {
			$this->sendResponse(new RedirectResponse($article->href, IResponse::S302_FOUND));
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}

}
