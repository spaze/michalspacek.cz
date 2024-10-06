<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Components;

use Nette\Utils\Html;

interface ArticleWithText
{

	public function getText(): Html;

}
