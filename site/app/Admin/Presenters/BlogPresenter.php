<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use DateTime;
use MichalSpacekCz\Articles\Blog\BlogPost;
use MichalSpacekCz\Articles\Blog\BlogPosts;
use MichalSpacekCz\Articles\Blog\Exceptions\BlogPostDoesNotExistException;
use MichalSpacekCz\Form\PostFormFactory;
use MichalSpacekCz\Formatter\TexyFormatter;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Utils\Html;

class BlogPresenter extends BasePresenter
{

	private ?BlogPost $post = null;


	public function __construct(
		private readonly BlogPosts $blogPosts,
		private readonly TexyFormatter $texyFormatter,
		private readonly PostFormFactory $postFormFactory,
	) {
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$posts = [];
		foreach ($this->blogPosts->getAll() as $post) {
			$posts[($post->published?->getTimestamp() ?: PHP_INT_MAX) . '|' . $post->slug] = $post;
		}
		krsort($posts, SORT_NATURAL);
		$this->template->posts = $posts;
		$this->template->pageTitle = 'Blog';
		$this->template->now = new DateTime();
	}


	public function actionAdd(): void
	{
		$this->template->pageTitle = 'Přidat příspěvek';
		$this->setView('edit');
	}


	protected function createComponentPost(): Form
	{
		return $this->postFormFactory->create(
			function (BlogPost $post): never {
				$this->blogPosts->add($post);
				$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postadded', [$post->titleTexy, $this->link('edit', [$post->postId]), $post->href]));
				$this->redirect('Blog:');
			},
			function (BlogPost $post): never {
				$post->previousSlugTags = $this->post->slugTags ?? [];
				$this->blogPosts->update($post);
				$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postupdated', [$post->titleTexy, $this->link('edit', [$post->postId]), $post->href]));
				$this->redirect('Blog:');
			},
			$this->template,
			$this->sendTemplate(...),
			$this->post,
		);
	}


	public function actionEdit(int $param): void
	{
		try {
			$this->post = $this->blogPosts->getById($param);
		} catch (BlogPostDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}
		$this->blogPosts->setTemplateTitleAndHeader($this->post, $this->template, Html::el()->setText('Příspěvek '));
	}

}
