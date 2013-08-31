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
		$discardedStatuses = $this->trainingApplications->getDiscardedStatuses();
		$trainings = $this->trainings->getUpcoming();
		foreach ($trainings as $training) {
			foreach ($training->dates as $date) {
				$applications = $this->trainingApplications->getByDate($date->dateId);
				$date->applications = array_filter($applications, function($value) use ($discardedStatuses) {
					return !in_array($value->status, $discardedStatuses);
				});
			}
		}
		$this->template->upcomingApplications = $trainings;

		$this->template->pageTitle = 'Administrace';
		$this->template->trackingEnabled = $this->webTracking->isEnabled();
	}


}
