<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Interviews;

use MichalSpacekCz\Form\InterviewFormFactory;
use MichalSpacekCz\Media\VideoThumbnails;

class InterviewInputsFactory
{

	public function __construct(
		private readonly VideoThumbnails $videoThumbnails,
		private readonly InterviewFormFactory $interviewFormFactory,
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
