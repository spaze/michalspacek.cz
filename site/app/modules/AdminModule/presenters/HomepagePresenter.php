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
		foreach ($this->trainings->getAllUpcoming() as $training) {
			foreach ($training->dates as $date) {
				$date->applications = $this->trainingApplications->getValidByDate($date->dateId);
				$dates[$date->start->getTimestamp()] = $date;
			}
		}
		ksort($dates);
		$this->template->upcomingApplications = $dates;
		$this->template->now = new \DateTime();
		$this->template->upcomingIds = $this->trainings->getPublicUpcomingIds();

		$this->template->pageTitle = 'Administrace';
		$this->template->trackingEnabled = $this->webTracking->isEnabled();
	}


}
