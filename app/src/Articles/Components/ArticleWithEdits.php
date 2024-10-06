<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Components;

use MichalSpacekCz\Articles\ArticleEdit;

interface ArticleWithEdits
{

	/**
	 * @return list<ArticleEdit>
	 */
	public function getEdits(): array;

}
