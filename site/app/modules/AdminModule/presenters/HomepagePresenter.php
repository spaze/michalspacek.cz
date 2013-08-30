<?php
namespace AdminModule;

/**
 * Homepage presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HomepagePresenter extends BasePresenter
{


	public function actionDefault()
	{
		$applications = $this->trainings->getUpcoming();
		foreach ($applications as $training) {
			foreach ($training->dates as $date) {
				$date->applications = $this->trainingApplications->getByDate($date->dateId);
			}
		}
		$this->template->upcomingApplications = $applications;

		$this->template->pageTitle = 'Administrace';
		$this->template->trackingEnabled = $this->webTracking->isEnabled();
	}


}
