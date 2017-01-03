<?php
namespace App\Presenters;

/**
 * A redirection presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class RedirectPresenter extends BasePresenter
{

	const GOOD_NIGHT = 5;

	/** @var \MichalSpacekCz\Training\Applications */
	protected $trainingApplications;


	/**
	 * @param \MichalSpacekCz\Training\Applications $trainingApplications
	 */
	public function __construct(\MichalSpacekCz\Training\Applications $trainingApplications)
	{
		$this->trainingApplications = $trainingApplications;
		parent::__construct();
	}


	public function actionFiles($token)
	{
		$application = $this->trainingApplications->getApplicationByToken($token);
		if ($application) {
			$this->redirect(':Trainings:files', $application->trainingAction, $token);
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}


	public function actionApplication($token)
	{
		$application = $this->trainingApplications->getApplicationByToken($token);
		if ($application) {
			$this->redirect(':Trainings:application', $application->trainingAction, $token);
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}


}
