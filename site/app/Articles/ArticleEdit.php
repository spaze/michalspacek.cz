<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles;

use DateTime;
use Nette\Utils\Html;

class ArticleEdit
{

	public DateTime $editedAt;

	public Html $summary;

	public string $summaryTexy;

}
