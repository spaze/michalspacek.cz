<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Interviews;

use MichalSpacekCz\Application\UiControl;
use MichalSpacekCz\Form\InterviewFormFactory;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Media\VideoThumbnails;

class InterviewInputs extends UiControl
{

	public function __construct(
		private readonly VideoThumbnails $videoThumbnails,
		private readonly InterviewFormFactory $interviewFormFactory,
		private readonly ?Interview $interview,
	) {
	}


	public function render(): void
	{
		$this->template->interview = $this->interview;
		$this->template->videoThumbnailWidth = $this->videoThumbnails->getWidth();
		$this->template->videoThumbnailHeight = $this->videoThumbnails->getHeight();
		$this->template->render(__DIR__ . '/interviewInputs.latte');
	}


	protected function createComponentInterview(): UiForm
	{
		return $this->interviewFormFactory->create(
			function (): never {
				$this->flashMessage($this->interview ? 'Rozhovor upraven' : 'Rozhovor přidán');
				$this->getPresenter()->redirect('Interviews:');
			},
			$this->interview,
		);
	}

}
