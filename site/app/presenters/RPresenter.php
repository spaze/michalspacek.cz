<?php
/**
 * A redirection presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class RPresenter extends BasePresenter
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


	public function actionS($token)
	{
		$application = $this->trainingApplications->getApplicationByToken($token);
		if ($application) {
			$this->redirect(':Trainings:soubory', $application->action, $token);
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}


	public function actionP($token)
	{
		$application = $this->trainingApplications->getApplicationByToken($token);
		if ($application) {
			$this->redirect(':Trainings:prihlaska', $application->action, $token);
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}


}
