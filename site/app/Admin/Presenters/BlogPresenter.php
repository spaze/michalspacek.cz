<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use DateTime;
use MichalSpacekCz\Articles\ArticleHeaderIcons;
use MichalSpacekCz\Articles\ArticleHeaderIconsFactory;
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
		private readonly ArticleHeaderIconsFactory $articleHeaderIconsFactory,
	) {
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$posts = [];
		foreach ($this->blogPosts->getAll() as $post) {
			$posts[($post->getPublishTime()?->getTimestamp() ?: PHP_INT_MAX) . '|' . $post->getSlug()] = $post;
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
				$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postadded', [$post->getTitleTexy(), $this->link('edit', [$post->getId()]), $post->getHref()]));
				$this->redirect('Blog:');
			},
			function (BlogPost $post): never {
				$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postupdated', [$post->getTitleTexy(), $this->link('edit', [$post->getId()]), $post->getHref()]));
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


	protected function createComponentArticleHeaderIcons(): ArticleHeaderIcons
	{
		return $this->articleHeaderIconsFactory->create();
	}

}
