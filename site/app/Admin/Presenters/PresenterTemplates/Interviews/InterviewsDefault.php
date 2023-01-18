<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters\PresenterTemplates\Interviews;

use Nette\Bridges\ApplicationLatte\Template;
use Nette\Database\Row;

class InterviewsDefault extends Template
{

	public string $pageTitle;

	/** @var list<Row> */
	public array $interviews;

	public null $interview;

}
