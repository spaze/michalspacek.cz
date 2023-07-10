<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles;

use MichalSpacekCz\Application\UiControl;
use MichalSpacekCz\Articles\Blog\BlogPost;

class ArticleSummary extends UiControl
{

	public function __construct(
		private readonly ArticleHeaderIconsFactory $articleHeaderIconsFactory,
	) {
	}


	public function render(ArticlePublishedElsewhere|BlogPost $article): void
	{
		$this->template->article = $article;
		$this->template->render(__DIR__ . '/articleSummary.latte');
	}


	protected function createComponentArticleHeaderIcons(): ArticleHeaderIcons
	{
		return $this->articleHeaderIconsFactory->create();
	}

}
