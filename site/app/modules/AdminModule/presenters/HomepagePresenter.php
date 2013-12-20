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
		$trainings = $this->trainings->getAllTrainingsInterval('-1 week');
		foreach ($this->trainings->getAllUpcoming() as $training) {
			foreach ($training->dates as $date) {
				$trainings[] = $date;
			}
		}
		$dates = array();
		foreach ($trainings as $date) {
			$date->applications = $this->trainingApplications->getValidByDate($date->dateId);
			$dates[$date->start->getTimestamp()] = $date;
		}
		ksort($dates);
		$this->template->upcomingApplications = $dates;
		$this->template->now = new \DateTime();
		$this->template->upcomingIds = $this->trainings->getPublicUpcomingIds();

		$this->template->pageTitle = 'Administrace';
		$this->template->trackingEnabled = $this->webTracking->isEnabled();
		$this->template->emailsToSend = count($this->trainingMails->getApplications());
	}


}
