<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use DateTime;
use MichalSpacekCz\Form\PostFormFactory;
use MichalSpacekCz\Formatter\Texy;
use MichalSpacekCz\Post\Data;
use MichalSpacekCz\Post\Post;
use MichalSpacekCz\Tags\Tags;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Http\IResponse;
use Nette\Utils\Html;
use Nette\Utils\Json;
use Spaze\ContentSecurityPolicy\Config as CspConfig;

class BlogPresenter extends BasePresenter
{

	private Post $blogPost;

	private Texy $texyFormatter;

	private Data $post;

	private Tags $tags;

	private PostFormFactory $postFormFactory;

	private CspConfig $contentSecurityPolicy;


	public function __construct(Post $blogPost, Texy $texyFormatter, Tags $tags, PostFormFactory $postFormFactory, CspConfig $contentSecurityPolicy)
	{
		$this->blogPost = $blogPost;
		$this->texyFormatter = $texyFormatter;
		$this->tags = $tags;
		$this->postFormFactory = $postFormFactory;
		parent::__construct();
		$this->contentSecurityPolicy = $contentSecurityPolicy;
	}


	public function actionDefault(): void
	{
		$posts = [];
		foreach ($this->blogPost->getAll() as $post) {
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
		$this->template->postId = null;
	}


	protected function createComponentAddPost(): Form
	{
		$form = $this->postFormFactory->create(function (Data $post): void {
			$this->blogPost->add($post);
			$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postadded', [$post->titleTexy, $this->link('edit', [$post->postId]), $post->href]));
			$this->redirect('Blog:');
		});
		return $form;
	}


	/**
	 * @param int $param
	 * @throws BadRequestException
	 */
	public function actionEdit(int $param): void
	{
		$this->post = $this->blogPost->getById($param);
		if (!$this->post) {
			throw new BadRequestException("Post id {$param} does not exist, yet");
		}

		$title = Html::el()->setText('Příspěvek ')->addHtml($this->post->title);
		$this->template->pageTitle = strip_tags((string)$title);
		$this->template->pageHeader = $title;
		$this->template->postId = $this->post->postId;
	}


	protected function createComponentEditPost(): Form
	{
		$form = $this->postFormFactory->create(function (Data $post): void {
			$post->postId = $this->post->postId;
			$post->previousSlugTags = $this->post->slugTags;
			$this->blogPost->update($post);
			$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postupdated', [$post->titleTexy, $this->link('edit', [$post->postId]), $post->href]));
			$this->redirect('Blog:');
		});

		$values = array(
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
			'twitterCard' => $this->post->twitterCard,
			'tags' => ($this->post->tags ? $this->tags->toString($this->post->tags) : null),
			'recommended' => (empty($this->post->recommended) ? null : Json::encode($this->post->recommended)),
			'cspSnippets' => $this->post->cspSnippets,
			'allowedTags' => $this->post->allowedTags,
		);
		$form->setDefaults($values);
		$form->getComponent('editSummary')
			->setDisabled($this->post->needsPreviewKey());
		$form->getComponent('submit')->caption = 'Upravit';
		return $form;
	}


	/**
	 * @throws AbortException
	 * @throws BadRequestException
	 */
	public function actionPreview(): void
	{
		if (!$this->isAjax()) {
			throw new BadRequestException('Not an AJAX call');
		}
		$this->texyFormatter->disableCache();
		$post = new Data();
		$post->slug = $this->request->getPost('slug');
		$post->titleTexy = $this->request->getPost('title');
		$post->leadTexy = (empty($this->request->getPost('lead')) ? null : $this->request->getPost('lead'));
		$post->textTexy = $this->request->getPost('text');
		$post->originallyTexy = (empty($this->request->getPost('originally')) ? null : $this->request->getPost('originally'));
		$post->published =  $this->request->getPost('published') ? new DateTime($this->request->getPost('published')) : null;
		$post->tags = (empty($this->request->getPost('tags')) ? [] : $this->tags->toArray($this->request->getPost('tags')));
		$post->slugTags = (empty($this->request->getPost('tags')) ? [] : $this->tags->toSlugArray($this->request->getPost('tags')));
		$post->recommended = (empty($this->request->getPost('recommended')) ? [] : Json::decode($this->request->getPost('recommended')));
		$post->cspSnippets = $this->request->getPost('cspSnippets') ?? [];
		$post->allowedTags = $this->request->getPost('allowedTags') ?? [];
		$this->blogPost->enrich($post);
		/** @var Template $preview */
		$preview = $this->createTemplate();
		$preview->setFile(__DIR__ . '/templates/Blog/preview.latte');
		$preview->post = $this->blogPost->format($post);
		$preview->edits = $this->blogPost->getEdits((int)$this->request->getPost('postId'));

		// Changing CSP in an AJAX response won't have the desired effect but for the sake of completeness...
		foreach ($post->cspSnippets as $snippet) {
			$this->contentSecurityPolicy->addSnippet($snippet);
		}

		$this->payload->status = IResponse::S200_OK;
		$this->payload->statusMessage = 'Formatted';
		$this->payload->formatted = (string)$preview;
		$this->sendPayload();
	}

}
