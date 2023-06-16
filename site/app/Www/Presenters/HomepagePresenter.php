<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Articles\Articles;
use MichalSpacekCz\Interviews\Interviews;
use MichalSpacekCz\Talks\Talks;
use MichalSpacekCz\Training\CompanyTrainings;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use MichalSpacekCz\Training\Trainings;

class HomepagePresenter extends BasePresenter
{

	public function __construct(
		private readonly Articles $articles,
		private readonly Interviews $interviews,
		private readonly Talks $talks,
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly Trainings $trainings,
		private readonly CompanyTrainings $companyTrainings,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageHeader = 'Michal Špaček';
		$this->template->articles = $this->articles->getAll(3);
		$this->template->talks = $this->talks->getAll(5);
		$this->template->favoriteTalks = $this->talks->getFavorites();
		$this->template->upcomingTalks = $this->talks->getUpcoming();
		$this->template->upcomingTrainings = $this->upcomingTrainingDates->getPublicUpcoming();
		$this->template->companyTrainings = $this->companyTrainings->getWithoutPublicUpcoming();
		$this->template->interviews = $this->interviews->getAll(5);
		$this->template->lastFreeSeats = $this->trainings->lastFreeSeatsAnyTraining($this->template->upcomingTrainings);
		$this->template->discontinued = $this->trainings->getAllDiscontinued();
	}

}
