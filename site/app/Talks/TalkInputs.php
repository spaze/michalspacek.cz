<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use MichalSpacekCz\Application\UiControl;
use MichalSpacekCz\Form\TalkFormFactory;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Media\VideoThumbnails;
use Nette\Utils\Html;

class TalkInputs extends UiControl
{

	public function __construct(
		private readonly VideoThumbnails $videoThumbnails,
		private readonly TalkFormFactory $talkFormFactory,
		private readonly ?Talk $talk,
	) {
	}


	public function render(): void
	{
		$this->template->talk = $this->talk;
		$this->template->videoThumbnailWidth = $this->videoThumbnails->getWidth();
		$this->template->videoThumbnailHeight = $this->videoThumbnails->getHeight();
		$this->template->render(__DIR__ . '/talkInputs.latte');
	}


	protected function createComponentTalk(): UiForm
	{
		return $this->talkFormFactory->create(
			function (Html $message): never {
				$this->flashMessage($message);
				$this->getPresenter()->redirect(':Admin:Talks:');
			},
			$this->talk,
		);
	}

}
