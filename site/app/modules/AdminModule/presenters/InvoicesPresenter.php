<?php
namespace AdminModule;

/**
 * Invoices presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class InvoicesPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\TrainingApplications */
	protected $trainingApplications;

	/** @var \MichalSpacekCz\TrainingDates */
	protected $trainingDates;

	/** @var \MichalSpacekCz\Trainings */
	protected $trainings;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\TrainingApplications $trainingApplications
	 * @param \MichalSpacekCz\TrainingDates $trainingDates
	 * @param \MichalSpacekCz\Trainings $trainings
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		\MichalSpacekCz\TrainingApplications $trainingApplications,
		\MichalSpacekCz\TrainingDates $trainingDates,
		\MichalSpacekCz\Trainings $trainings
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
