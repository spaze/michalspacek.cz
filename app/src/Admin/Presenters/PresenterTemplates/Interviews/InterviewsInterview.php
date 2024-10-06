<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters\PresenterTemplates\Interviews;

use MichalSpacekCz\Interviews\Interview;

class InterviewsInterview
{

	public function __construct(
		public string $pageTitle,
		public Interview $interview,
	) {
	}

}
