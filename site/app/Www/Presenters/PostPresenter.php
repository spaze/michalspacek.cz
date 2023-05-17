<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use DateInterval;
use MichalSpacekCz\Post\LocaleUrls;
use MichalSpacekCz\Post\Post;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Dates;
use Spaze\ContentSecurityPolicy\Config as CspConfig;

class PostPresenter extends BasePresenter
{

	/** @var array<string, array{slug: string, preview: string|null}> */
	private array $localeLinkParams = [];


	public function __construct(
		private readonly Post $blogPost,
		private readonly Dates $trainingDates,
		private readonly LocaleUrls $localeUrls,
		private readonly CspConfig $contentSecurityPolicy,
	) {
		parent::__construct();
	}


	public function actionDefault(string $slug, ?string $preview = null): void
	{
		$post = $this->blogPost->get($slug, $preview);
		if ($slug !== $post->slug) {
			$this->redirectPermanent($this->getAction(), [$post->slug, $preview]);
		}
		if ($preview !== null) {
			if (!$post->needsPreviewKey()) {
				$this->redirect($this->getAction(), $slug);
			}
			$this->template->robots = 'noindex';
		}
		if ($post->postId === null) {
			throw new ShouldNotHappenException('Never thought it would be possible to have a published blog post without an id');
		}
		$edits = $this->blogPost->getEdits($post->postId);
		$this->template->post = $post;
		$this->template->pageTitle = htmlspecialchars_decode(strip_tags((string)$post->title));
		$this->template->pageHeader = $post->title;
		$this->template->upcomingTrainings = $this->trainingDates->getPublicUpcoming();
		$this->template->edits = $edits;
		/** @var DateInterval|false $interval */
		$interval = ($edits && $post->published ? current($edits)->editedAt->diff($post->published) : false);
		if ($edits && $interval && $interval->days >= $this->blogPost->getUpdatedInfoThreshold()) {
			$this->template->edited = current($edits)->editedAt;
		} else {
			$this->template->edited = null;
		}

		foreach ($this->localeUrls->get($post->slug) as $localePost) {
			$this->localeLinkParams[$localePost->locale] = ['slug' => $localePost->slug, 'preview' => ($localePost->needsPreviewKey() ? $localePost->previewKey : null)];
		}
		foreach ($post->cspSnippets as $snippet) {
			$this->contentSecurityPolicy->addSnippet($snippet);
		}
	}


	/**
	 * Get original module:presenter:action for locale links.
	 *
	 * @return string
	 */
	protected function getLocaleLinkAction(): string
	{
		return (count($this->localeLinkParams) > 1 ? parent::getLocaleLinkAction() : 'Www:Articles:');
	}


	/**
	 * Translated locale parameters for blog posts.
	 *
	 * @return array<string, array{slug: string, preview: string|null}>
	 */
	protected function getLocaleLinkParams(): array
	{
		return $this->localeLinkParams;
	}

}
