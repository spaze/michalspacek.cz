<?php
namespace App\AdminModule\Presenters;

use MichalSpacekCz\Training;

/**
 * Homepage presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HomepagePresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Training\Applications */
	protected $trainingApplications;

	/** @var \MichalSpacekCz\Training\Mails */
	protected $trainingMails;

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;

	/** @var \MichalSpacekCz\WebTracking */
	protected $webTracking;


	/**
	 * @param \MichalSpacekCz\Training\Applications $trainingApplications
	 * @param \MichalSpacekCz\Training\Mails $trainingMails
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 * @param \MichalSpacekCz\WebTracking $webTracking
	 */
	public function __construct(
		Training\Applications $trainingApplications,
		Training\Mails $trainingMails,
		Training\Dates $trainingDates,
		\MichalSpacekCz\WebTracking $webTracking
	)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingMails = $trainingMails;
		$this->trainingDates = $trainingDates;
		$this->webTracking = $webTracking;
		parent::__construct();
	}


	public function actionDefault()
	{
		$trainings = $this->trainingDates->getAllTrainingsInterval('-1 week');
		foreach ($this->trainingDates->getAllUpcoming() as $training) {
			foreach ($training->dates as $date) {
				$trainings[] = $date;
			}
		}
		$dates = array();
		foreach ($trainings as $date) {
			$date->applications = $this->trainingApplications->getValidByDate($date->dateId);
			$date->validCount = count($date->applications);
			$dates[$date->start->getTimestamp()] = $date;
		}
		ksort($dates);
		$this->template->upcomingApplications = $dates;
		$this->template->now = new \DateTime();
		$this->template->upcomingIds = $this->trainingDates->getPublicUpcomingIds();

		$this->template->pageTitle = 'Administrace';
		$this->template->trackingEnabled = $this->webTracking->isEnabled();
		$this->template->emailsToSend = count($this->trainingMails->getApplications());
		$this->template->unpaidInvoices = $this->trainingApplications->getValidUnpaidCount();
		list($this->template->preliminaryTotal, $this->template->preliminaryDateSet) = $this->trainingApplications->getPreliminaryCounts();
	}

}
