<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Homepage;

use MichalSpacekCz\Articles\Articles;
use MichalSpacekCz\Articles\ArticleSummary;
use MichalSpacekCz\Articles\ArticleSummaryFactory;
use MichalSpacekCz\Interviews\Interviews;
use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\Talks\Talks;
use MichalSpacekCz\Talks\TalksList;
use MichalSpacekCz\Talks\TalksListFactory;
use MichalSpacekCz\Training\Company\CompanyTrainings;
use MichalSpacekCz\Training\DateList\UpcomingTrainingDatesList;
use MichalSpacekCz\Training\DateList\UpcomingTrainingDatesListFactory;
use MichalSpacekCz\Training\Discontinued\DiscontinuedTrainings;

final class HomepagePresenter extends BasePresenter
{

	public function __construct(
		private readonly Articles $articles,
		private readonly Interviews $interviews,
		private readonly Talks $talks,
		private readonly CompanyTrainings $companyTrainings,
		private readonly DiscontinuedTrainings $discontinuedTrainings,
		private readonly UpcomingTrainingDatesListFactory $upcomingTrainingDatesListFactory,
		private readonly ArticleSummaryFactory $articleSummaryFactory,
		private readonly TalksListFactory $talksListFactory,
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
		$this->template->discontinued = $this->discontinuedTrainings->getAllDiscontinued();
	}


	protected function createComponentUpcomingDatesList(): UpcomingTrainingDatesList
	{
		return $this->upcomingTrainingDatesListFactory->create();
	}


	protected function createComponentArticleSummary(): ArticleSummary
	{
		return $this->articleSummaryFactory->create();
	}


	protected function createComponentTalksList(): TalksList
	{
		return $this->talksListFactory->create();
	}

}
