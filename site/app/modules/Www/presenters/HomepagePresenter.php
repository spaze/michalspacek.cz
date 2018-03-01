<?php
namespace App\WwwModule\Presenters;

use MichalSpacekCz\Training;

/**
 * Homepage presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HomepagePresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Articles */
	protected $articles;

	/** @var \MichalSpacekCz\Interviews */
	protected $interviews;

	/** @var \MichalSpacekCz\Talks */
	protected $talks;

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;

	/** @var \MichalSpacekCz\Training\Trainings */
	protected $trainings;

	/** @var \MichalSpacekCz\Training\CompanyTrainings */
	protected $companyTrainings;


	/**
	 * @param \MichalSpacekCz\Articles $articles
	 * @param \MichalSpacekCz\Interviews $interviews
	 * @param \MichalSpacekCz\Talks $talks
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 * @param \MichalSpacekCz\Training\Trainings $trainings
	 * @param \MichalSpacekCz\Training\CompanyTrainings $companyTrainings
	 */
	public function __construct(
		\MichalSpacekCz\Articles $articles,
		\MichalSpacekCz\Interviews $interviews,
		\MichalSpacekCz\Talks $talks,
		Training\Dates $trainingDates,
		Training\Trainings $trainings,
		Training\CompanyTrainings $companyTrainings
	)
	{
		$this->articles = $articles;
		$this->interviews = $interviews;
		$this->talks = $talks;
		$this->trainingDates = $trainingDates;
		$this->trainings = $trainings;
		$this->companyTrainings = $companyTrainings;
		parent::__construct();
	}


	public function renderDefault()
	{
		$this->template->articles          = $this->articles->getAll(3);
		$this->template->talks             = $this->talks->getAll(5);
		$this->template->favoriteTalks     = $this->talks->getFavorites();
		$this->template->upcomingTalks     = $this->talks->getUpcoming();
		$this->template->upcomingTrainings = $this->trainingDates->getPublicUpcoming();
		$this->template->companyTrainings  = $this->companyTrainings->getWithoutPublicUpcoming();
		$this->template->interviews        = $this->interviews->getAll(5);
		$this->template->lastFreeSeats     = $this->trainings->lastFreeSeatsAnyTraining($this->template->upcomingTrainings);
		$this->template->discontinued = $this->trainings->getDiscontinued();
	}

}
