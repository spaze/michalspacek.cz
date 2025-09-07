<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Interviews;

use MichalSpacekCz\Interviews\Interview;

final class InterviewTemplateParameters
{

	public function __construct(
		public string $pageTitle,
		public Interview $interview,
	) {
	}

}
