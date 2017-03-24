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

	/** @var \MichalSpacekCz\Api\Certificates */
	protected $certificates;

	/** @var \MichalSpacekCz\WebTracking */
	protected $webTracking;


	/**
	 * @param \MichalSpacekCz\Training\Applications $trainingApplications
	 * @param \MichalSpacekCz\Training\Mails $trainingMails
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 * @param \MichalSpacekCz\Api\Certificates $certificates
	 * @param \MichalSpacekCz\WebTracking $webTracking
	 */
	public function __construct(
		Training\Applications $trainingApplications,
		Training\Mails $trainingMails,
		Training\Dates $trainingDates,
		\MichalSpacekCz\Api\Certificates $certificates,
		\MichalSpacekCz\WebTracking $webTracking
	)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingMails = $trainingMails;
		$this->trainingDates = $trainingDates;
		$this->certificates = $certificates;
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
			$date->canceledApplications = $this->trainingApplications->getCanceledPaidByDate($date->dateId);
			$date->validCount = count($date->applications);
			$date->requiresAttention = ($date->canceledApplications || ($date->status == Training\Dates::STATUS_CANCELED && $date->validCount));
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
		$this->template->certificates = $certificates = $this->certificates->getNewest();
		$this->template->certificatesNeedAttention = $this->certsNeedAttention($certificates);
		list($this->template->preliminaryTotal, $this->template->preliminaryDateSet) = $this->trainingApplications->getPreliminaryCounts();
	}


	/**
	 * Check if at least one certificate is expired or expires soon.
	 *
	 * @param array $certificates
	 * @return boolean
	 */
	private function certsNeedAttention(array $certificates): bool
	{
		foreach ($certificates as $certificate) {
			if ($certificate->expired || $certificate->expiringSoon) {
				return true;
			}
		}
		return false;
	}

}
