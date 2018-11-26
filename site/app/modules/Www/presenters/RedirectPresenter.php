<?php
namespace App\WwwModule\Presenters;

/**
 * A redirection presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class RedirectPresenter extends BasePresenter
{

	private const GOOD_NIGHT = 5;

	/** @var \MichalSpacekCz\Training\Applications */
	protected $trainingApplications;

	/** @var \MichalSpacekCz\Articles */
	protected $articles;


	/**
	 * @param \MichalSpacekCz\Training\Applications $trainingApplications
	 * @param \MichalSpacekCz\Articles $articles
	 */
	public function __construct(\MichalSpacekCz\Training\Applications $trainingApplications, \MichalSpacekCz\Articles $articles)
	{
		$this->trainingApplications = $trainingApplications;
		$this->articles = $articles;
		parent::__construct();
	}


	public function actionFiles($token)
	{
		$application = $this->trainingApplications->getApplicationByToken($token);
		if ($application) {
			$this->redirect(':Www:Trainings:files', $application->trainingAction, $token);
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}


	public function actionApplication($token)
	{
		$application = $this->trainingApplications->getApplicationByToken($token);
		if ($application) {
			$this->redirect(':Www:Trainings:application', $application->trainingAction, $token);
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}


	public function actionNewestArticleByTag($token)
	{
		$article = current($this->articles->getAllByTags($token, 1));
		if ($article) {
			$this->sendResponse(new \Nette\Application\Responses\RedirectResponse($article->href, \Nette\Http\IResponse::S302_FOUND));
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}

}
