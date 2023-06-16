<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use DateTime;
use MichalSpacekCz\Form\TrainingInvoiceFormFactory;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Dates\TrainingDates;
use Nette\Forms\Form;

class InvoicesPresenter extends BasePresenter
{

	/** @var array<int, string> */
	private array $allUnpaidInvoiceIds = [];


	public function __construct(
		private readonly Applications $trainingApplications,
		private readonly TrainingDates $trainingDates,
		private readonly TrainingInvoiceFormFactory $trainingInvoiceFormFactory,
	) {
		parent::__construct();
	}


	public function actionUnpaid(): void
	{
		$dates = [];
		foreach ($this->trainingDates->getWithUnpaid() as $date) {
			$unpaidApplications = $this->trainingApplications->getValidUnpaidByDate($date->dateId);
			foreach ($unpaidApplications as $application) {
				$this->allUnpaidInvoiceIds[] = $application->invoiceId;
			}
			$date->applications = $unpaidApplications;
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
			function (int $count): never {
				if ($count) {
					$this->flashMessage('Počet zaplacených přihlášek: ' . $count);
				} else {
					$this->flashMessage('Nebyla zaplacena žádná přihláška', 'notice');
				}
				$this->redirect('this');
			},
			function (): never {
				$this->flashMessage('Došlo k chybě při označování přihlášky jako zaplacené', 'error');
				$this->redirect('this');
			},
			$this->allUnpaidInvoiceIds,
		);
	}

}
