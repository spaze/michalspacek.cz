<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters\PresenterTemplates\Interviews;

use MichalSpacekCz\Interviews\Interview;
use Nette\Bridges\ApplicationLatte\Template;

class InterviewsDefault extends Template
{

	public string $pageTitle;

	/** @var list<Interview> */
	public array $interviews;

	public null $interview;

}
