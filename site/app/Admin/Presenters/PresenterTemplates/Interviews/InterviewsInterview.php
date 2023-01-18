<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters\PresenterTemplates\Interviews;

use Nette\Bridges\ApplicationLatte\Template;
use Nette\Database\Row;

class InterviewsInterview extends Template
{

	public string $pageTitle;

	/** @var Row<mixed> */
	public Row $interview;

	public int $videoThumbnailWidth;

	public int $videoThumbnailHeight;

}
