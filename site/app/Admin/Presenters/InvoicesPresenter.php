<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use DateTime;
use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Form\TrainingInvoice;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Dates;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;

class InvoicesPresenter extends BasePresenter
{

	private Applications $trainingApplications;

	private Dates $trainingDates;

	private TrainingControlsFactory $trainingControlsFactory;


	public function __construct(Applications $trainingApplications, Dates $trainingDates, TrainingControlsFactory $trainingControlsFactory)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingDates = $trainingDates;
		parent::__construct();
		$this->trainingControlsFactory = $trainingControlsFactory;
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
		$form = new TrainingInvoice($this, $formName, $this->trainingControlsFactory);
		$form->onSuccess[] = [$this, 'submittedApplication'];
		return $form;
	}


	/**
	 * @param Form $form
	 * @param ArrayHash<int|string> $values
	 */
	public function submittedApplication(Form $form, ArrayHash $values): void
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
