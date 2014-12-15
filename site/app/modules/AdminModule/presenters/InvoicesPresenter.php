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

	/** @var \MichalSpacekCz\Training\Trainings */
	protected $trainings;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\Training\Applications $trainingApplications
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 * @param \MichalSpacekCz\Training\Trainings $trainings
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		Training\Applications $trainingApplications,
		Training\Dates $trainingDates,
		Training\Trainings $trainings
	)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingDates = $trainingDates;
		$this->trainings = $trainings;
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
		$this->template->upcomingIds = $this->trainings->getPublicUpcomingIds();

		$this->template->pageTitle = 'Nezaplacené faktury';
	}


}
