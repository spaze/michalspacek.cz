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
		$dates = array();
		foreach ($this->trainings->getPublicUpcoming() as $training) {
			foreach ($training->dates as $date) {
				$date->trainingName = $training->name;
				$applications = $this->trainingApplications->getByDate($date->dateId);
				$date->applications = array_filter($applications, function($value) use ($discardedStatuses) {
					return !in_array($value->status, $discardedStatuses);
				});
				$dates[$date->start->getTimestamp()] = $date;
			}
		}
		ksort($dates);
		$this->template->upcomingApplications = $dates;

		$this->template->pageTitle = 'Administrace';
		$this->template->trackingEnabled = $this->webTracking->isEnabled();
	}


}
