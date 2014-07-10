<?php
/**
 * A redirection presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class RedirectPresenter extends BasePresenter
{

	const GOOD_NIGHT = 5;

	/** @var \MichalSpacekCz\TrainingApplications */
	protected $trainingApplications;


	/**
	 * @param \MichalSpacekCz\TrainingApplications $trainingApplications
	 */
	public function __construct(\MichalSpacekCz\TrainingApplications $trainingApplications)
	{
		$this->trainingApplications = $trainingApplications;
		parent::__construct();
	}


	public function actionFiles($token)
	{
		$application = $this->trainingApplications->getApplicationByToken($token);
		if ($application) {
			$this->redirect(':Trainings:files', $application->action, $token);
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}


	public function actionApplication($token)
	{
		$application = $this->trainingApplications->getApplicationByToken($token);
		if ($application) {
			$this->redirect(':Trainings:application', $application->action, $token);
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}


}
