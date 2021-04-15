<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Post;

use DateTime;
use Nette\Utils\Html;

class Edit
{

	public DateTime $editedAt;

	/** @var Html<Html|string> */
	public Html $summary;

	public string $summaryTexy;

}
