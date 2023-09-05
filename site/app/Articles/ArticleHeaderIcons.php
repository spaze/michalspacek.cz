<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles;

use DateTime;
use MichalSpacekCz\Application\UiControl;
use MichalSpacekCz\Articles\Blog\BlogPost;
use MichalSpacekCz\Articles\Blog\BlogPosts;
use MichalSpacekCz\Articles\Components\ArticleWithEdits;

class ArticleHeaderIcons extends UiControl
{

	public function __construct(
		private readonly BlogPosts $blogPosts,
	) {
	}


	public function render(ArticlePublishedElsewhere|BlogPost $article): void
	{
		$this->template->post = $article;
		$this->template->edited = $this->getEdited($article);
		$this->template->render(__DIR__ . '/articleHeaderIcons.latte');
	}


	private function getEdited(ArticlePublishedElsewhere|BlogPost $article): ?DateTime
	{
		$edits = $article instanceof ArticleWithEdits ? $article->getEdits() : [];
		$interval = $edits && $article->getPublishTime() ? current($edits)->getEditedAt()->diff($article->getPublishTime()) : false;
		if ($edits && $interval && $interval->days >= $this->blogPosts->getUpdatedInfoThreshold()) {
			return current($edits)->getEditedAt();
		}
		return null;
	}

}
