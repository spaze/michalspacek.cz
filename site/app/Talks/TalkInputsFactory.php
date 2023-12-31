<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use MichalSpacekCz\Form\TalkFormFactory;
use MichalSpacekCz\Media\VideoThumbnails;

readonly class TalkInputsFactory
{

	public function __construct(
		private VideoThumbnails $videoThumbnails,
		private TalkFormFactory $talkFormFactory,
	) {
	}


	public function create(): TalkInputs
	{
		return new TalkInputs($this->videoThumbnails, $this->talkFormFactory, null);
	}


	public function createFor(Talk $talk): TalkInputs
	{
		return new TalkInputs($this->videoThumbnails, $this->talkFormFactory, $talk);
	}

}
