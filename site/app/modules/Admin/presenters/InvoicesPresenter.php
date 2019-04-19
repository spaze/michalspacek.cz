<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use DateTime;
use MichalSpacekCz\Form\TrainingInvoice;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Dates;

/**
 * Invoices presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class InvoicesPresenter extends BasePresenter
{

	/** @var Applications */
	protected $trainingApplications;

	/** @var Dates */
	protected $trainingDates;


	public function __construct(Applications $trainingApplications, Dates $trainingDates)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingDates = $trainingDates;
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


	protected function createComponentInvoice(string $formName): TrainingInvoice
	{
		$form = new TrainingInvoice($this, $formName, $this->translator);
		$form->onSuccess[] = [$this, 'submittedApplication'];
		return $form;
	}


	public function submittedApplication(TrainingInvoice $form, $values): void
	{
		$count = $this->trainingApplications->setPaidDate($values->invoice, $values->paid);
		if ($count) {
			$this->flashMessage('Počet zaplacených přihlášek: ' . $count);
		} else {
			$this->flashMessage('Nebyla zaplacena žádná přihláška', 'notice');
		}
		$this->redirect('this');
	}

}
