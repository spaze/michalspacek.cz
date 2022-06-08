<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use DateTime;
use MichalSpacekCz\Form\TrainingInvoiceFormFactory;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Dates;
use Nette\Forms\Form;

class InvoicesPresenter extends BasePresenter
{

	public function __construct(
		private readonly Applications $trainingApplications,
		private readonly Dates $trainingDates,
		private readonly TrainingInvoiceFormFactory $trainingInvoiceFormFactory,
	) {
		parent::__construct();
	}


	public function actionUnpaid(): void
	{
		$dates = array();
		foreach ($this->trainingDates->getWithUnpaid() as $date) {
			$date->applications = $this->trainingApplications->getValidUnpaidByDate($date->dateId);
			$date->validCount = count($date->applications);
			$date->canceledApplications = false;
			$dates[$date->start->getTimestamp()] = $date;
		}
		ksort($dates);
		$this->template->unpaidApplications = $dates;
		$this->template->now = new DateTime();
		$this->template->upcomingIds = $this->trainingDates->getPublicUpcomingIds();
		$this->template->pageTitle = 'Nezaplacené faktury';
	}


	protected function createComponentInvoice(): Form
	{
		return $this->trainingInvoiceFormFactory->create(
			function (int $count): void {
				if ($count) {
					$this->flashMessage('Počet zaplacených přihlášek: ' . $count);
				} else {
					$this->flashMessage('Nebyla zaplacena žádná přihláška', 'notice');
				}
				$this->redirect('this');
			},
		);
	}

}
