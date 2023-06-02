<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Blog;

use DateTime;
use Nette\Utils\Html;

class BlogPostEdit
{

	public DateTime $editedAt;

	/** @var Html<Html|string> */
	public Html $summary;

	public string $summaryTexy;

}
