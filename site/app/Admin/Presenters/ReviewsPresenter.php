<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Training\Reviews;
use MichalSpacekCz\Training\Trainings;

class ReviewsPresenter extends BasePresenter
{

	public function __construct(
		private readonly Trainings $trainings,
		private readonly Reviews $trainingReviews,
	) {
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->template->pageTitle = 'Ohlasy';
		$this->template->trainings = $this->trainings->getNames();
	}


	public function actionTraining(string $param): void
	{
		$training = $this->trainings->getById((int)$param);

		$this->template->pageTitle = 'Ohlasy na ' . $training->name;
		$this->template->reviews = $this->trainingReviews->getAllReviews((int)$param);
	}

}
