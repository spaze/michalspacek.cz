<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use DateTime;
use MichalSpacekCz\Articles\Blog\BlogPost;
use MichalSpacekCz\Articles\Blog\BlogPosts;
use MichalSpacekCz\Articles\Blog\Exceptions\BlogPostDoesNotExistException;
use MichalSpacekCz\Form\PostFormFactory;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Tags\Tags;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Utils\Html;
use Nette\Utils\Json;

class BlogPresenter extends BasePresenter
{

	private BlogPost $post;


	public function __construct(
		private readonly BlogPosts $blogPosts,
		private readonly TexyFormatter $texyFormatter,
		private readonly Tags $tags,
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
	}


	protected function createComponentAddPost(): Form
	{
		return $this->postFormFactory->create(
			function (BlogPost $post): never {
				$this->blogPosts->add($post);
				$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postadded', [$post->titleTexy, $this->link('edit', [$post->postId]), $post->href]));
				$this->redirect('Blog:');
			},
			$this->template,
			$this->sendTemplate(...),
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


	protected function createComponentEditPost(): Form
	{
		$form = $this->postFormFactory->create(
			function (BlogPost $post): never {
				$post->previousSlugTags = $this->post->slugTags;
				$this->blogPosts->update($post);
				$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postupdated', [$post->titleTexy, $this->link('edit', [$post->postId]), $post->href]));
				$this->redirect('Blog:');
			},
			$this->template,
			$this->sendTemplate(...),
			$this->post->postId,
		);

		$values = [
			'translationGroup' => $this->post->translationGroupId,
			'locale' => $this->post->localeId,
			'title' => $this->post->titleTexy,
			'slug' => $this->post->slug,
			'published' => $this->post->published?->format('Y-m-d H:i'),
			'previewKey' => $this->post->previewKey,
			'lead' => $this->post->leadTexy,
			'text' => $this->post->textTexy,
			'originally' => $this->post->originallyTexy,
			'ogImage' => $this->post->ogImage,
			'twitterCard' => $this->post->twitterCard?->getCard(),
			'tags' => ($this->post->tags ? $this->tags->toString($this->post->tags) : null),
			'recommended' => (empty($this->post->recommended) ? null : Json::encode($this->post->recommended)),
			'cspSnippets' => $this->post->cspSnippets,
			'allowedTags' => $this->post->allowedTags,
			'omitExports' => $this->post->omitExports,
		];
		$form->setDefaults($values);
		$form->getComponent('editSummary')
			->setDisabled($this->post->needsPreviewKey());
		$form->getComponent('submit')->caption = 'Upravit';
		return $form;
	}

}
