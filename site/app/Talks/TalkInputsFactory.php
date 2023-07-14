<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use MichalSpacekCz\Form\TalkFormFactory;
use MichalSpacekCz\Media\VideoThumbnails;

class TalkInputsFactory
{

	public function __construct(
		private readonly VideoThumbnails $videoThumbnails,
		private readonly TalkFormFactory $talkFormFactory,
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
