<?php
declare(strict_types = 1);

namespace App\BlogModule\Presenters;

/**
 * Post presenter.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
class PostPresenter extends \App\Presenters\BasePresenter
{

	/** @var \MichalSpacekCz\Blog\Post */
	protected $blogPost;


	/**
	 * @param \MichalSpacekCz\Blog\Post $blogPost
	 */
	public function __construct(\MichalSpacekCz\Blog\Post $blogPost)
	{
		$this->blogPost = $blogPost;
		parent::__construct();
	}


	public function actionDefault(string $post): void
	{
		$post = $this->blogPost->get($post);
		$this->template->post = $post;
		$this->template->pageTitle = strip_tags((string)$post->title);
	}

}
