<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Components;

use DateTime;

interface ArticleWithUpdateTime
{

	public function getUpdateTime(): ?DateTime;

}
