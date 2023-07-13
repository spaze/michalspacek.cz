<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use MichalSpacekCz\Application\UiControl;
use MichalSpacekCz\Form\TalkFormFactory;
use MichalSpacekCz\Media\VideoThumbnails;
use Nette\Forms\Form;
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


	protected function createComponentTalk(): Form
	{
		return $this->talkFormFactory->create(
			function (Html $message): never {
				$this->flashMessage($message);
				$this->redirect('Talks:');
			},
			$this->talk,
		);
	}

}
