<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use MichalSpacekCz\Application\UiControl;
use MichalSpacekCz\Form\TrainingDateFormFactory;
use MichalSpacekCz\Form\UiForm;

class TrainingDateInputs extends UiControl
{

	public function __construct(
		private readonly TrainingDateFormFactory $trainingDateFormFactory,
		private readonly ?TrainingDate $trainingDate,
	) {
	}


	public function render(string $htmlId): void
	{
		$this->template->id = $htmlId;
		$this->template->render(__DIR__ . '/trainingDateInputs.latte');
	}


	protected function createComponentDate(): UiForm
	{
		return $this->trainingDateFormFactory->create(
			function (): never {
				$this->getPresenter()->redirect(':Admin:Trainings:');
			},
			function (int $dateId): never {
				$this->flashMessage('Termín upraven');
				$this->getPresenter()->redirect($this->getPresenter()->getAction(), $dateId);
			},
			$this->trainingDate,
		);
	}

}
