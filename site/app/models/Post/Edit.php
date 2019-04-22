<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Post;

use DateTime;
use Nette\Utils\Html;

class Edit
{

	/** @var DateTime */
	public $editedAt;

	/** @var Html */
	public $summary;

	/** @var string */
	public $summaryTexy;

}
