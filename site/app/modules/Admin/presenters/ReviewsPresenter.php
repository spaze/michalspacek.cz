<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use MichalSpacekCz\Training;

/**
 * Reviews presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ReviewsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Training\Trainings */
	protected $trainings;

	/** @var \MichalSpacekCz\Training\Reviews */
	protected $trainingReviews;


	/**
	 * @param \MichalSpacekCz\Training\Trainings $trainings
	 * @param \MichalSpacekCz\Training\Reviews $trainingReviews
	 */
	public function __construct(Training\Trainings $trainings, Training\Reviews $trainingReviews)
	{
		$this->trainings = $trainings;
		$this->trainingReviews = $trainingReviews;
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
