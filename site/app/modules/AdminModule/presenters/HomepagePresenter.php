<?php
namespace AdminModule;

use \MichalSpacekCz\Training;

/**
 * Homepage presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HomepagePresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Training\Applications */
	protected $trainingApplications;

	/** @var \MichalSpacekCz\Training\Mails */
	protected $trainingMails;

	/** @var \MichalSpacekCz\Training\Trainings */
	protected $trainings;

	/** @var \MichalSpacekCz\WebTracking */
	protected $webTracking;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\Training\Applications $trainingApplications
	 * @param \MichalSpacekCz\Training\Mails $trainingMails
	 * @param \MichalSpacekCz\Training\Trainings $trainings
	 * @param \MichalSpacekCz\WebTracking $webTracking
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		Training\Applications $trainingApplications,
		Training\Mails $trainingMails,
		Training\Trainings $trainings,
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
