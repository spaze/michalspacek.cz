<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Components;

use MichalSpacekCz\Articles\ArticleEdit;
use Nette\Utils\Html;

interface ArticleWithTextAndEdits
{

	public function getText(): Html;


	/**
	 * @return list<ArticleEdit>
	 */
	public function getEdits(): array;

}
