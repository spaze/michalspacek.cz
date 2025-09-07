<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Post;

use MichalSpacekCz\Articles\ArticleHeaderIcons;
use MichalSpacekCz\Articles\ArticleHeaderIconsFactory;
use MichalSpacekCz\Articles\Blog\BlogPostLocaleUrls;
use MichalSpacekCz\Articles\Blog\BlogPosts;
use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use Override;
use Spaze\ContentSecurityPolicy\CspConfig;

final class PostPresenter extends BasePresenter
{

	/** @var array<string, array{slug: string, preview: string|null}> */
	private array $localeLinkParams = [];


	public function __construct(
		private readonly BlogPosts $blogPosts,
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly BlogPostLocaleUrls $blogPostLocaleUrls,
		private readonly CspConfig $contentSecurityPolicy,
		private readonly ArticleHeaderIconsFactory $articleHeaderIconsFactory,
	) {
		parent::__construct();
	}


	public function actionDefault(string $slug, ?string $preview = null): void
	{
		$post = $this->blogPosts->get($slug, $preview);
		if ($slug !== $post->getSlug()) {
			$this->redirectPermanent($this->getAction(), [$post->getSlug(), $preview]);
		}
		if ($preview !== null) {
			if (!$post->needsPreviewKey()) {
				$this->redirect($this->getAction(), $slug);
			}
			$this->template->robots = 'noindex';
		}
		if (!$post->hasId()) {
			throw new ShouldNotHappenException('Never thought it would be possible to have a published blog post without an id');
		}
		$this->template->post = $post;
		$this->template->pageTitle = htmlspecialchars_decode(strip_tags((string)$post->getTitle()));
		$this->template->pageHeader = $post->getTitle();
		$this->template->upcomingTrainings = $this->upcomingTrainingDates->getPublicUpcoming();

		foreach ($this->blogPostLocaleUrls->get($post->getSlug()) as $localePost) {
			$this->localeLinkParams[$localePost->getLocale()] = ['slug' => $localePost->getSlug(), 'preview' => $localePost->getPreviewKey()];
		}
		foreach ($post->getCspSnippets() as $snippet) {
			$this->contentSecurityPolicy->addSnippet($snippet);
		}
	}


	/**
	 * Get original module:presenter:action for locale links.
	 */
	#[Override]
	protected function getLocaleLinkAction(): string
	{
		return (count($this->localeLinkParams) > 1 ? parent::getLocaleLinkAction() : 'Www:Articles:');
	}


	/**
	 * Translated locale parameters for blog posts.
	 *
	 * @return array<string, array{slug: string, preview: string|null}>
	 */
	#[Override]
	protected function getLocaleLinkParams(): array
	{
		return $this->localeLinkParams;
	}


	protected function createComponentArticleHeaderIcons(): ArticleHeaderIcons
	{
		return $this->articleHeaderIconsFactory->create();
	}

}
