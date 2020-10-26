<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use MichalSpacekCz\Articles;
use MichalSpacekCz\Interviews;
use MichalSpacekCz\Talks;
use MichalSpacekCz\Training\CompanyTrainings;
use MichalSpacekCz\Training\Dates;
use MichalSpacekCz\Training\Trainings;

class HomepagePresenter extends BasePresenter
{

	/** @var Articles */
	protected $articles;

	/** @var Interviews */
	protected $interviews;

	/** @var Talks */
	protected $talks;

	/** @var Dates */
	protected $trainingDates;

	/** @var Trainings */
	protected $trainings;

	/** @var CompanyTrainings */
	protected $companyTrainings;


	public function __construct(
		Articles $articles,
		Interviews $interviews,
		Talks $talks,
		Dates $trainingDates,
		Trainings $trainings,
		CompanyTrainings $companyTrainings
	) {
		$this->articles = $articles;
		$this->interviews = $interviews;
		$this->talks = $talks;
		$this->trainingDates = $trainingDates;
		$this->trainings = $trainings;
		$this->companyTrainings = $companyTrainings;
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageHeader = 'Michal Špaček';
		$this->template->articles          = $this->articles->getAll(3);
		$this->template->talks             = $this->talks->getAll(5);
		$this->template->favoriteTalks     = $this->talks->getFavorites();
		$this->template->upcomingTalks     = $this->talks->getUpcoming();
		$this->template->upcomingTrainings = $this->trainingDates->getPublicUpcoming();
		$this->template->companyTrainings  = $this->companyTrainings->getWithoutPublicUpcoming();
		$this->template->interviews        = $this->interviews->getAll(5);
		$this->template->lastFreeSeats     = $this->trainings->lastFreeSeatsAnyTraining($this->template->upcomingTrainings);
		$this->template->discontinued = $this->trainings->getAllDiscontinued();
	}

}
