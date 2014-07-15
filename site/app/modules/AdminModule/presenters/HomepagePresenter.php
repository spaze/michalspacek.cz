<?php
namespace AdminModule;

/**
 * Homepage presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HomepagePresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\TrainingApplications */
	protected $trainingApplications;

	/** @var \MichalSpacekCz\TrainingMails */
	protected $trainingMails;

	/** @var \MichalSpacekCz\Trainings */
	protected $trainings;

	/** @var \MichalSpacekCz\WebTracking */
	protected $webTracking;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\TrainingApplications $trainingApplications
	 * @param \MichalSpacekCz\TrainingMails $trainingMails
	 * @param \MichalSpacekCz\Trainings $trainings
	 * @param \MichalSpacekCz\WebTracking $webTracking
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		\MichalSpacekCz\TrainingApplications $trainingApplications,
		\MichalSpacekCz\TrainingMails $trainingMails,
		\MichalSpacekCz\Trainings $trainings,
		\MichalSpacekCz\WebTracking $webTracking
	)
	{
		$this->trainingApplications = $trainingApplications;
		$this->trainingMails = $trainingMails;
		$this->trainings = $trainings;
		$this->webTracking = $webTracking;
		parent::__construct($translator);
	}


	public function actionDefault()
	{
		$discardedStatuses = $this->trainingApplications->getDiscardedStatuses();
		$trainings = $this->trainings->getAllTrainingsInterval('-1 week');
		foreach ($this->trainings->getAllUpcoming() as $training) {
			foreach ($training->dates as $date) {
				$trainings[] = $date;
			}
		}
		$dates = array();
		foreach ($trainings as $date) {
			$date->applications = $this->trainingApplications->getValidByDate($date->dateId);
			$dates[$date->start->getTimestamp()] = $date;
		}
		ksort($dates);
		$this->template->upcomingApplications = $dates;
		$this->template->now = new \DateTime();
		$this->template->upcomingIds = $this->trainings->getPublicUpcomingIds();

		$this->template->pageTitle = 'Administrace';
		$this->template->trackingEnabled = $this->webTracking->isEnabled();
		$this->template->emailsToSend = count($this->trainingMails->getApplications());
	}


}
