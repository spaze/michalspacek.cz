<?php
declare(strict_types = 1);

namespace App\BlogModule\Presenters;

/**
 * Post presenter.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
class PostPresenter extends \App\WwwModule\Presenters\BasePresenter
{
	/** @var \MichalSpacekCz\Blog\Post */
	protected $blogPost;

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;

	/** @var string[][] */
	protected $localeLinkParams = [];


	/**
	 * @param \MichalSpacekCz\Blog\Post $blogPost
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 */
	public function __construct(\MichalSpacekCz\Blog\Post $blogPost, \MichalSpacekCz\Training\Dates $trainingDates)
	{
		$this->blogPost = $blogPost;
		$this->trainingDates = $trainingDates;
		parent::__construct();
	}


	public function actionDefault(string $slug, ?string $preview = null): void
	{
		$post = $this->blogPost->get($slug, $preview);
		if ($preview !== null) {
			if (!$post->needsPreviewKey()) {
				$this->redirect($this->getAction(), $slug);
			}
			$this->template->robots = 'noindex';
		}
		$this->template->post = $post;
		$this->template->pageTitle = strip_tags((string)$post->title);
		$this->template->pageHeader = $post->title;
		$this->template->upcomingTrainings = $this->trainingDates->getPublicUpcoming();

		foreach ($this->blogPost->getLocaleUrls($post->slug) as $post) {
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
		return ($this->localeLinkParams ? parent::getLocaleLinkAction() : 'Www:Articles:');
	}


	/**
	 * Translated locale parameters for blog posts.
	 *
	 * @return array
	 */
	protected function getLocaleLinkParams(): array
	{
		return ($this->localeLinkParams ?: []);
	}

}
