<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Interviews;

use MichalSpacekCz\Interviews\Interview;
use Nette\Utils\Html;

final class InterviewsInterviewTemplateParameters
{

	public function __construct(
		public Html $pageTitle,
		public Interview $interview,
	) {
	}

}
