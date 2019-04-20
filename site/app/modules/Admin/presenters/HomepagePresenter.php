<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use DateTime;
use MichalSpacekCz\Certificates;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Dates;
use MichalSpacekCz\Training\Mails;

class HomepagePresenter extends BasePresenter
{

	protected $haveBacklink = false;

	/** @var Applications */
	protected $trainingApplications;

	/** @var Mails */
	protected $trainingMails;

	/** @var Dates */
	protected $trainingDates;

	/** @var Certificates */
	protected $certificates;


	public function __construct(Applications $trainingApplications, Mails $trainingMails, Dates $trainingDates, Certificates $certificates)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingMails = $trainingMails;
		$this->trainingDates = $trainingDates;
		$this->certificates = $certificates;
		parent::__construct();
	}


	public function actionDefault(): void
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
			$date->requiresAttention = ($date->canceledApplications || ($date->status == Dates::STATUS_CANCELED && $date->validCount));
			$dates[$date->start->getTimestamp()] = $date;
		}
		ksort($dates);
		$this->template->upcomingApplications = $dates;
		$this->template->now = new DateTime();
		$this->template->upcomingIds = $this->trainingDates->getPublicUpcomingIds();

		$this->template->pageTitle = 'Administrace';
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
