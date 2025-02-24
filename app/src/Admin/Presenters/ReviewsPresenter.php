<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Training\Reviews\TrainingReviews;
use MichalSpacekCz\Training\Trainings\Trainings;

class ReviewsPresenter extends BasePresenter
{

	public function __construct(
		private readonly Trainings $trainings,
		private readonly TrainingReviews $trainingReviews,
	) {
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->template->pageTitle = 'Ohlasy';
		$this->template->trainings = $this->trainings->getNames();
	}


	public function actionTraining(int $param): void
	{
		$training = $this->trainings->getById($param);
		$this->template->pageTitle = 'Ohlasy na ' . $training->getName()->render();
		$this->template->reviews = $this->trainingReviews->getAllReviews($param);
	}

}
