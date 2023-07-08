<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Form\TrainingInvoiceFormFactory;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\DateList\DateListOrder;
use MichalSpacekCz\Training\DateList\TrainingApplicationsList;
use MichalSpacekCz\Training\DateList\TrainingApplicationsListFactory;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\TrainingDates;
use Nette\Forms\Form;

class InvoicesPresenter extends BasePresenter
{

	/** @var array<int, string> */
	private array $allUnpaidInvoiceIds = [];

	/** @var list<TrainingDate> */
	private array $datesWithUnpaid = [];


	public function __construct(
		private readonly Applications $trainingApplications,
		private readonly TrainingDates $trainingDates,
		private readonly TrainingInvoiceFormFactory $trainingInvoiceFormFactory,
		private readonly TrainingApplicationsListFactory $trainingApplicationsListFactory,
	) {
		parent::__construct();
	}


	public function actionUnpaid(): void
	{
		$dates = [];
		foreach ($this->trainingDates->getWithUnpaid() as $date) {
			$unpaidApplications = $this->trainingApplications->getValidUnpaidByDate($date->getId());
			foreach ($unpaidApplications as $application) {
				$this->allUnpaidInvoiceIds[] = $application->invoiceId;
			}
			$date->setApplications($unpaidApplications);
			$dates[$date->getStart()->getTimestamp()] = $date;
		}
		ksort($dates);
		$this->template->unpaidApplications = (bool)$dates;
		$this->datesWithUnpaid = array_values($dates);

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


	protected function createComponentTrainingApplicationsList(): TrainingApplicationsList
	{
		return $this->trainingApplicationsListFactory->create($this->datesWithUnpaid, DateListOrder::Asc);
	}

}
