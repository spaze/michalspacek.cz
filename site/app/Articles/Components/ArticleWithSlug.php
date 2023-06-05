<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Components;

interface ArticleWithSlug
{

	public function getSlug(): string;

}
