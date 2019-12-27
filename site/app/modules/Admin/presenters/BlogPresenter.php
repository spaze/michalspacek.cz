<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use DateTime;
use MichalSpacekCz\Form\Post as PostForm;
use MichalSpacekCz\Formatter\Texy;
use MichalSpacekCz\Post;
use MichalSpacekCz\Post\Data;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\InvalidLinkException;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Forms\Form;
use Nette\Http\IResponse;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use Nette\Utils\Json;
use UnexpectedValueException;

class BlogPresenter extends BasePresenter
{

	/** @var Post */
	protected $blogPost;

	/** @var Texy */
	protected $texyFormatter;

	/** @var Data */
	private $post;


	public function __construct(Post $blogPost, Texy $texyFormatter)
	{
		$this->blogPost = $blogPost;
		$this->texyFormatter = $texyFormatter;
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$posts = [];
		foreach ($this->blogPost->getAll() as $post) {
			$posts[$post->published->getTimestamp() . $post->slug] = $post;
		}
		krsort($posts);
		$this->template->posts = $posts;
		$this->template->pageTitle = 'Blog';
	}


	public function actionAdd(): void
	{
		$this->template->pageTitle = 'Přidat příspěvek';
		$this->template->postId = null;
	}


	/**
	 * @param string $formName
	 * @return PostForm
	 */
	protected function createComponentAddPost(string $formName): PostForm
	{
		$form = new PostForm($this, $formName, $this->blogPost);
		$form->onSuccess[] = [$this, 'submittedAddpost'];
		return $form;
	}


	/**
	 * @param Form $form
	 * @param ArrayHash<integer|string> $values
	 * @throws AbortException
	 * @throws InvalidLinkException
	 */
	public function submittedAddPost(Form $form, ArrayHash $values): void
	{
		try {
			$post = new Data();
			$post->translationGroupId = (empty($values->translationGroup) ? null : $values->translationGroup);
			$post->localeId = $values->locale;
			$post->locale = $this->blogPost->getLocaleById($values->locale);
			$post->slug = $values->slug;
			$post->titleTexy = $values->title;
			$post->leadTexy = (empty($values->lead) ? null : $values->lead);
			$post->textTexy = $values->text;
			$post->originallyTexy = (empty($values->originally) ? null : $values->originally);
			$post->published = new DateTime($values->published);
			$post->previewKey = (empty($values->previewKey) ? null : $values->previewKey);
			$post->ogImage = (empty($values->ogImage) ? null : $values->ogImage);
			$post->tags = (empty($values->tags) ? [] : $this->blogPost->tagsToArray($values->tags));
			$post->slugTags = (empty($values->tags) ? [] : $this->blogPost->getSlugTags($values->tags));
			$post->recommended = (empty($values->recommended) ? null : Json::decode($values->recommended));
			$post->twitterCard = (empty($values->twitterCard) ? null : $values->twitterCard);
			$this->blogPost->enrich($post);
			$this->blogPost->add($post);
			$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postadded', [$post->titleTexy, $this->link('edit', [$post->postId]), $post->href]));
		} catch (UnexpectedValueException $e) {
			$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.duplicateslug'), 'error');
		}
		$this->redirect('Blog:');
	}


	/**
	 * @param integer $param
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


	/**
	 * @param string $formName
	 * @return PostForm
	 */
	protected function createComponentEditPost(string $formName): PostForm
	{
		$form = new PostForm($this, $formName, $this->blogPost);
		$form->setPost($this->post);
		$form->onSuccess[] = [$this, 'submittedEditPost'];
		return $form;
	}


	/**
	 * @param Form $form
	 * @param ArrayHash<integer|string> $values
	 * @throws AbortException
	 * @throws InvalidLinkException
	 */
	public function submittedEditPost(Form $form, ArrayHash $values): void
	{
		$post = new Data();
		$post->postId = $this->post->postId;
		$post->translationGroupId = (empty($values->translationGroup) ? null : $values->translationGroup);
		$post->localeId = $values->locale;
		$post->locale = $this->blogPost->getLocaleById($values->locale);
		$post->slug = $values->slug;
		$post->titleTexy = $values->title;
		$post->leadTexy = (empty($values->lead) ? null : $values->lead);
		$post->textTexy = $values->text;
		$post->originallyTexy = (empty($values->originally) ? null : $values->originally);
		$post->published = new DateTime($values->published);
		$post->previewKey = (empty($values->previewKey) ? null : $values->previewKey);
		$post->ogImage = (empty($values->ogImage) ? null : $values->ogImage);
		$post->tags = (empty($values->tags) ? []: $this->blogPost->tagsToArray($values->tags));
		$post->slugTags = (empty($values->tags) ? [] : $this->blogPost->getSlugTags($values->tags));
		$post->previousSlugTags = $this->post->slugTags;
		$post->recommended = (empty($values->recommended) ? null : Json::decode($values->recommended));
		$post->twitterCard = (empty($values->twitterCard) ? null : $values->twitterCard);
		$post->editSummary = (empty($values->editSummary) ? null : $values->editSummary);
		$this->blogPost->enrich($post);
		$this->blogPost->update($post);
		$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postupdated', [$post->titleTexy, $this->link('edit', [$post->postId]), $post->href]));
		$this->redirect('Blog:');
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
		$post->title = $this->request->getPost('title');
		$post->leadTexy = (empty($this->request->getPost('lead')) ? null : $this->request->getPost('lead'));
		$post->textTexy = $this->request->getPost('text');
		$post->originallyTexy = (empty($this->request->getPost('originally')) ? null : $this->request->getPost('originally'));
		$post->published = new DateTime($this->request->getPost('published'));
		$post->tags = (empty($this->request->getPost('tags')) ? null : $this->blogPost->tagsToArray($this->request->getPost('tags')));
		$post->slugTags = (empty($this->request->getPost('tags')) ? null : $this->blogPost->getSlugTags($this->request->getPost('tags')));
		$post->recommended = (empty($this->request->getPost('recommended')) ? null : Json::decode($this->request->getPost('recommended')));
		$this->blogPost->enrich($post);
		/** @var Template $preview */
		$preview = $this->createTemplate();
		$preview->setFile(__DIR__ . '/templates/Blog/preview.latte');
		$preview->post = $this->blogPost->format($post);
		$preview->edits = $this->blogPost->getEdits((int)$this->request->getPost('postId'));

		$this->payload->status = IResponse::S200_OK;
		$this->payload->statusMessage = 'Formatted';
		$this->payload->formatted = (string)$preview;
		$this->sendPayload();
	}

}
