<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use MichalSpacekCz\Post;
use MichalSpacekCz\Post\LocaleUrls;
use MichalSpacekCz\Training\Dates;
use Nette\Application\AbortException;
use Spaze\ContentSecurityPolicy\Config as CspConfig;

class PostPresenter extends BasePresenter
{
	/** @var Post */
	protected $blogPost;

	/** @var LocaleUrls */
	protected $localeUrls;

	/** @var Dates */
	protected $trainingDates;

	/** @var string[][] */
	protected $localeLinkParams = [];

	private CspConfig $contentSecurityPolicy;


	public function __construct(Post $blogPost, Dates $trainingDates, LocaleUrls $localeUrls, CspConfig $contentSecurityPolicy)
	{
		$this->blogPost = $blogPost;
		$this->localeUrls = $localeUrls;
		$this->trainingDates = $trainingDates;
		$this->contentSecurityPolicy = $contentSecurityPolicy;
		parent::__construct();
	}


	/**
	 * @param string $slug
	 * @param string|null $preview
	 * @throws AbortException
	 */
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
		$edits = $this->blogPost->getEdits($post->postId);
		$this->template->post = $post;
		$this->template->pageTitle = htmlspecialchars_decode(strip_tags((string)$post->title));
		$this->template->pageHeader = $post->title;
		$this->template->upcomingTrainings = $this->trainingDates->getPublicUpcoming();
		$this->template->edits = $edits;
		if ($edits && current($edits)->editedAt->diff($post->published)->days >= $this->blogPost->getUpdatedInfoThreshold()) {
			$this->template->edited = current($edits)->editedAt;
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
	 * @return string[][]
	 */
	protected function getLocaleLinkParams(): array
	{
		return $this->localeLinkParams;
	}

}
