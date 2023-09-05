<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Spaze\ContentSecurityPolicy\CspConfig;

class BlogPostPreview
{

	public function __construct(
		private readonly TexyFormatter $texyFormatter,
		private readonly BlogPosts $blogPosts,
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly CspConfig $contentSecurityPolicy,
	) {
	}


	/**
	 * @param callable(?DefaultTemplate): void $sendTemplate
	 */
	public function sendPreview(BlogPost $post, DefaultTemplate $template, callable $sendTemplate): void
	{
		$this->texyFormatter->disableCache();
		$template->setFile(__DIR__ . '/../../Www/Presenters/templates/Post/default.latte');
		$template->post = $post;
		$template->edits = $post->hasId() ? $post->getEdits() : [];
		$template->upcomingTrainings = $this->upcomingTrainingDates->getPublicUpcoming();
		$template->showBreadcrumbsMenu = false;
		$template->showHeaderTabs = false;
		$template->showFooter = false;
		$template->localeLinks = [];
		$this->blogPosts->setTemplateTitleAndHeader($post, $template);
		foreach ($post->getCspSnippets() as $snippet) {
			$this->contentSecurityPolicy->addSnippet($snippet);
		}
		$sendTemplate($template);
	}

}
