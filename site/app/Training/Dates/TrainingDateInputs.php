<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use MichalSpacekCz\Application\UiControl;
use MichalSpacekCz\Form\TrainingDateFormFactory;
use Nette\Forms\Form;

class TrainingDateInputs extends UiControl
{

	public function __construct(
		private readonly TrainingDateFormFactory $trainingDateFormFactory,
		private readonly ?TrainingDate $trainingDate,
		private readonly ?int $redirectParam,
	) {
	}


	public function render(string $htmlId): void
	{
		$this->template->id = $htmlId;
		$this->template->render(__DIR__ . '/trainingDateInputs.latte');
	}


	protected function createComponentDate(): Form
	{
		return $this->trainingDateFormFactory->create(
			function (): never {
				$this->getPresenter()->redirect('Trainings:');
			},
			function (): never {
				$this->flashMessage('Termín upraven');
				$this->getPresenter()->redirect($this->getPresenter()->getAction(), $this->redirectParam);
			},
			$this->trainingDate,
		);
	}

}
