<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Homepage;

use MichalSpacekCz\Http\Session\SessionGarbageCollectorStatusMessage;
use MichalSpacekCz\Http\Session\SessionGarbageCollectorStatusMessageFactory;
use MichalSpacekCz\Presentation\Admin\BasePresenter;
use MichalSpacekCz\Tls\Certificates;
use MichalSpacekCz\Tls\CertificatesList\TlsCertificatesList;
use MichalSpacekCz\Tls\CertificatesList\TlsCertificatesListFactory;
use MichalSpacekCz\Training\Applications\TrainingApplications;
use MichalSpacekCz\Training\DateList\DateListOrder;
use MichalSpacekCz\Training\DateList\TrainingApplicationsList;
use MichalSpacekCz\Training\DateList\TrainingApplicationsListFactory;
use MichalSpacekCz\Training\Dates\TrainingDates;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use MichalSpacekCz\Training\Mails\TrainingMails;
use MichalSpacekCz\Training\Preliminary\PreliminaryTrainings;

final class HomepagePresenter extends BasePresenter
{

	protected bool $haveBacklink = false;


	public function __construct(
		private readonly TrainingApplications $trainingApplications,
		private readonly PreliminaryTrainings $trainingPreliminaryApplications,
		private readonly TrainingMails $trainingMails,
		private readonly TrainingDates $trainingDates,
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly Certificates $certificates,
		private readonly TrainingApplicationsListFactory $trainingApplicationsListFactory,
		private readonly TlsCertificatesListFactory $tlsCertificatesListFactory,
		private readonly SessionGarbageCollectorStatusMessageFactory $sessionGarbageCollectorStatusMessageFactory,
	) {
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->template->pageTitle = 'Administrace';
		$this->template->emailsToSend = count($this->trainingMails->getApplications());
		$this->template->unpaidInvoices = $this->trainingApplications->getValidUnpaidCount();
		$this->template->certificatesWithWarningCount = count($this->certificates->getNewestWithWarnings());
		[$this->template->preliminaryTotal, $this->template->preliminaryDateSet] = $this->trainingPreliminaryApplications->getPreliminaryCounts();
		$this->template->pastWithPersonalData = count($this->trainingDates->getPastWithPersonalData());
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


	protected function createComponentTlsCertificatesList(): TlsCertificatesList
	{
		return $this->tlsCertificatesListFactory->create($this->certificates->getNewestWithWarnings());
	}


	protected function createComponentSessionGarbageCollectorStatusMessage(): SessionGarbageCollectorStatusMessage
	{
		return $this->sessionGarbageCollectorStatusMessageFactory->create();
	}

}
