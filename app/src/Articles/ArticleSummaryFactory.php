<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles;

interface ArticleSummaryFactory
{

	public function create(): ArticleSummary;

}
