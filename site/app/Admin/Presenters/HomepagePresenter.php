<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Tls\Certificate;
use MichalSpacekCz\Tls\Certificates;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\DateList\DateListOrder;
use MichalSpacekCz\Training\DateList\TrainingApplicationsList;
use MichalSpacekCz\Training\DateList\TrainingApplicationsListFactory;
use MichalSpacekCz\Training\Dates\TrainingDates;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use MichalSpacekCz\Training\Mails;

class HomepagePresenter extends BasePresenter
{

	protected bool $haveBacklink = false;


	public function __construct(
		private readonly Applications $trainingApplications,
		private readonly Mails $trainingMails,
		private readonly TrainingDates $trainingDates,
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly Certificates $certificates,
		private readonly TrainingApplicationsListFactory $trainingApplicationsListFactory,
	) {
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->template->pageTitle = 'Administrace';
		$this->template->emailsToSend = count($this->trainingMails->getApplications());
		$this->template->unpaidInvoices = $this->trainingApplications->getValidUnpaidCount();
		$this->template->certificates = $certificates = $this->certificates->getNewest();
		$this->template->certificatesNeedAttention = $this->certsNeedAttention($certificates);
		[$this->template->preliminaryTotal, $this->template->preliminaryDateSet] = $this->trainingApplications->getPreliminaryCounts();
		$this->template->pastWithPersonalData = count($this->trainingDates->getPastWithPersonalData());
	}


	/**
	 * Check if at least one certificate is expired or expires soon.
	 *
	 * @param array<int, Certificate> $certificates
	 * @return bool
	 */
	private function certsNeedAttention(array $certificates): bool
	{
		foreach ($certificates as $certificate) {
			if ($certificate->isExpired() || $certificate->isExpiringSoon()) {
				return true;
			}
		}
		return false;
	}


	protected function createComponentTrainingApplicationsList(): TrainingApplicationsList
	{
		$trainings = $this->trainingDates->getAllTrainingsInterval('-1 week');
		foreach ($this->upcomingTrainingDates->getAllUpcoming() as $training) {
			foreach ($training->getDates() as $date) {
				$trainings[] = $date;
			}
		}
		$dates = [];
		foreach ($trainings as $date) {
			$date->setApplications($this->trainingApplications->getValidByDate($date->getId()));
			$date->setCanceledApplications($this->trainingApplications->getCanceledPaidByDate($date->getId()));
			$dates[$date->getStart()->getTimestamp()] = $date;
		}
		ksort($dates);
		return $this->trainingApplicationsListFactory->create(array_values($dates), DateListOrder::Asc);
	}

}
