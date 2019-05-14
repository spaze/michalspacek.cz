<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use MichalSpacekCz\Post;
use MichalSpacekCz\Post\LocaleUrls;
use MichalSpacekCz\Training\Dates;
use Nette\Application\AbortException;

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


	public function __construct(Post $blogPost, Dates $trainingDates, LocaleUrls $localeUrls)
	{
		$this->blogPost = $blogPost;
		$this->localeUrls = $localeUrls;
		$this->trainingDates = $trainingDates;
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

		foreach ($this->localeUrls->get($post->slug) as $post) {
			$this->localeLinkParams[$post->locale] = ['slug' => $post->slug, 'preview' => ($post->needsPreviewKey() ? $post->previewKey : null)];
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
	 * @return array
	 */
	protected function getLocaleLinkParams(): array
	{
		return $this->localeLinkParams;
	}

}
