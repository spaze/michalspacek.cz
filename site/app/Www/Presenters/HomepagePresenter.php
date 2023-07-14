<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Articles\Articles;
use MichalSpacekCz\Articles\ArticleSummary;
use MichalSpacekCz\Articles\ArticleSummaryFactory;
use MichalSpacekCz\Interviews\Interviews;
use MichalSpacekCz\Talks\Talks;
use MichalSpacekCz\Training\CompanyTrainings;
use MichalSpacekCz\Training\DateList\UpcomingTrainingDatesList;
use MichalSpacekCz\Training\DateList\UpcomingTrainingDatesListFactory;
use MichalSpacekCz\Training\Trainings;

class HomepagePresenter extends BasePresenter
{

	public function __construct(
		private readonly Articles $articles,
		private readonly Interviews $interviews,
		private readonly Talks $talks,
		private readonly Trainings $trainings,
		private readonly CompanyTrainings $companyTrainings,
		private readonly UpcomingTrainingDatesListFactory $upcomingTrainingDatesListFactory,
		private readonly ArticleSummaryFactory $articleSummaryFactory,
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
		$this->template->companyTrainings = $this->companyTrainings->getWithoutPublicUpcoming();
		$this->template->interviews = $this->interviews->getAll(5);
		$this->template->discontinued = $this->trainings->getAllDiscontinued();
	}


	protected function createComponentUpcomingDatesList(): UpcomingTrainingDatesList
	{
		return $this->upcomingTrainingDatesListFactory->create(null, true);
	}


	protected function createComponentArticleSummary(): ArticleSummary
	{
		return $this->articleSummaryFactory->create();
	}

}
