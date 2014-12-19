<?php
namespace AdminModule;

use MichalSpacekCz\Training;

/**
 * Invoices presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class InvoicesPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Training\Applications */
	protected $trainingApplications;

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\Training\Applications $trainingApplications
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		Training\Applications $trainingApplications,
		Training\Dates $trainingDates
	)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingDates = $trainingDates;
		parent::__construct($translator);
	}


	public function actionUnpaid()
	{
		$dates = array();
		foreach ($this->trainingDates->getWithUnpaid() as $date) {
			$date->applications = $this->trainingApplications->getValidUnpaidByDate($date->dateId);
			$dates[$date->start->getTimestamp()] = $date;
		}
		ksort($dates);
		$this->template->unpaidApplications = $dates;
		$this->template->now = new \DateTime();
		$this->template->upcomingIds = $this->trainingDates->getPublicUpcomingIds();

		$this->template->pageTitle = 'Nezaplacené faktury';
	}


	protected function createComponentInvoice($formName)
	{
		$form = new \MichalSpacekCz\Form\TrainingInvoice($this, $formName);
		$form->onSuccess[] = $this->submittedApplication;
	}


	public function submittedApplication(\MichalSpacekCz\Form\TrainingInvoice $form)
	{
		$values = $form->getValues();
		$count = $this->trainingApplications->setPaidDate($values->invoice, $values->paid);
		if ($count) {
			$this->flashMessage('Počet zaplacených přihlášek: ' . $count);
		} else {
			$this->flashMessage('Nebyla zaplacena žádná přihláška', 'notice');
		}
		$this->redirect('this');
	}

}
