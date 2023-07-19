<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Reviews;

use MichalSpacekCz\Application\UiControl;
use MichalSpacekCz\Form\TrainingReviewFormFactory;
use Nette\Forms\Form;

class TrainingReviewInputs extends UiControl
{

	public function __construct(
		private readonly TrainingReviewFormFactory $trainingReviewFormFactory,
		private readonly bool $showApplications,
		private readonly int $dateId,
		private readonly ?TrainingReview $review,
	) {
	}


	public function render(): void
	{
		$this->template->showApplications = $this->showApplications;
		$this->template->render(__DIR__ . '/trainingReviewInputs.latte');
	}


	protected function createComponentTrainingReview(): Form
	{
		return $this->trainingReviewFormFactory->create(
			function (int $dateId): never {
				$this->getPresenter()->redirect('date', $dateId);
			},
			$this->dateId,
			$this->review,
		);
	}

}
