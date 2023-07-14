<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters\PresenterTemplates\Interviews;

use MichalSpacekCz\Interviews\Interview;
use Nette\Bridges\ApplicationLatte\Template;

class InterviewsInterview extends Template
{

	public string $pageTitle;

	public Interview $interview;

}
