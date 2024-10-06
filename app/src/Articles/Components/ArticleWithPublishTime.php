<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Components;

use DateTime;

interface ArticleWithPublishTime
{

	public function getPublishTime(): ?DateTime;

}
