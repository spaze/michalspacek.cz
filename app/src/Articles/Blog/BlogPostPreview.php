<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Spaze\ContentSecurityPolicy\CspConfig;

final readonly class BlogPostPreview
{

	public function __construct(
		private TexyFormatter $texyFormatter,
		private BlogPosts $blogPosts,
		private UpcomingTrainingDates $upcomingTrainingDates,
		private CspConfig $contentSecurityPolicy,
	) {
	}


	/**
	 * @param callable(): BlogPost $createPost
	 * @param callable(?DefaultTemplate): void $sendTemplate
	 */
	public function sendPreview(callable $createPost, DefaultTemplate $template, callable $sendTemplate): void
	{
		$this->texyFormatter->disableCache();
		$post = $createPost();
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
