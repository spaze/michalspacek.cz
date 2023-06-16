<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Training\Dates\TrainingDates;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Spaze\ContentSecurityPolicy\CspConfig;

class BlogPostPreview
{

	public function __construct(
		private readonly TexyFormatter $texyFormatter,
		private readonly BlogPosts $blogPosts,
		private readonly TrainingDates $trainingDates,
		private readonly CspConfig $contentSecurityPolicy,
	) {
	}


	/**
	 * @param BlogPost $post
	 * @param DefaultTemplate $template
	 * @param callable(): never $sendTemplate
	 * @return never
	 */
	public function sendPreview(BlogPost $post, DefaultTemplate $template, callable $sendTemplate): never
	{
		$this->texyFormatter->disableCache();
		$template->setFile(__DIR__ . '/../Www/Presenters/templates/Post/default.latte');
		$template->post = $this->blogPosts->format($post);
		$template->edits = $post->postId ? $post->edits : [];
		$template->upcomingTrainings = $this->trainingDates->getPublicUpcoming();
		$template->showBreadcrumbsMenu = false;
		$template->showHeaderTabs = false;
		$template->showFooter = false;
		$this->blogPosts->setTemplateTitleAndHeader($post, $template);
		foreach ($post->cspSnippets as $snippet) {
			$this->contentSecurityPolicy->addSnippet($snippet);
		}
		$sendTemplate();
	}

}
