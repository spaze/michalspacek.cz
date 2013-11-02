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


	public function actionS($token)
	{
		$application = $this->trainingApplications->getApplicationByToken($token);
		if ($application) {
			$this->redirect(':Skoleni:soubory', $application->action, $token);
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}


	public function actionP($token)
	{
		$application = $this->trainingApplications->getApplicationByToken($token);
		if ($application) {
			$this->redirect(':Skoleni:prihlaska', $application->action, $token);
		} else {
			sleep(self::GOOD_NIGHT);
		}
	}


}
