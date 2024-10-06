<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Interviews;

use MichalSpacekCz\Form\InterviewFormFactory;
use MichalSpacekCz\Media\VideoThumbnails;

readonly class InterviewInputsFactory
{

	public function __construct(
		private VideoThumbnails $videoThumbnails,
		private InterviewFormFactory $interviewFormFactory,
	) {
	}


	public function create(): InterviewInputs
	{
		return new InterviewInputs($this->videoThumbnails, $this->interviewFormFactory, null);
	}


	public function createFor(Interview $interview): InterviewInputs
	{
		return new InterviewInputs($this->videoThumbnails, $this->interviewFormFactory, $interview);
	}

}
