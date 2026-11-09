<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Post;

use MichalSpacekCz\Application\Locale\LocaleLink;
use MichalSpacekCz\Articles\ArticleHeaderIcons;
use MichalSpacekCz\Articles\ArticleHeaderIconsFactory;
use MichalSpacekCz\Articles\Blog\BlogPostLocaleUrls;
use MichalSpacekCz\Articles\Blog\BlogPosts;
use MichalSpacekCz\Articles\Blog\BlogPostSitemapEntries;
use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Sitemap\InSitemap;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use Override;
use Spaze\ContentSecurityPolicy\CspConfig;

final class PostPresenter extends BasePresenter
{

	/** @var array<string, array{slug: string}> */
	private array $localeLinkParams = [];

	private bool $preview = false;


	public function __construct(
		private readonly BlogPosts $blogPosts,
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly BlogPostLocaleUrls $blogPostLocaleUrls,
		private readonly CspConfig $contentSecurityPolicy,
		private readonly ArticleHeaderIconsFactory $articleHeaderIconsFactory,
	) {
		parent::__construct();
	}


	#[InSitemap(BlogPostSitemapEntries::class)]
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
			$this->preview = true;
		}
		if (!$post->hasId()) {
			throw new ShouldNotHappenException('Never thought it would be possible to have a published blog post without an id');
		}
		$this->template->post = $post;
		$this->template->pageTitle = htmlspecialchars_decode(strip_tags((string)$post->getTitle()));
		$this->template->pageHeader = $post->getTitle();
		$this->template->upcomingTrainings = $this->upcomingTrainingDates->getPublicUpcoming();

		$this->localeLinkParams = $this->blogPostLocaleUrls->getLinkParams($post);
		foreach ($post->getCspSnippets() as $snippet) {
			$this->contentSecurityPolicy->addSnippet($snippet);
		}
	}


	/**
	 * A post that isn't out yet isn't at its address in any language until it is.
	 *
	 * @return array<string, LocaleLink>
	 */
	#[Override]
	protected function getAlternateLinks(): array
	{
		return $this->preview ? [] : parent::getAlternateLinks();
	}


	/**
	 * The language switcher leads to the list of articles when the post isn't translated.
	 */
	#[Override]
	protected function getLocaleLinksGeneratorDestination(): string
	{
		return (count($this->localeLinkParams) > 1 ? parent::getLocaleLinksGeneratorDestination() : 'Www:Articles:');
	}


	/**
	 * Translated locale parameters for blog posts.
	 *
	 * @return array<string, array{slug: string}>
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
