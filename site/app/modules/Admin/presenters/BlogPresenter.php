<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

/**
 * Blog presenter.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
class BlogPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Blog\Post */
	protected $blogPost;

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/** @var \MichalSpacekCz\Blog\Post\Data */
	private $post;


	/**
	 * @param \MichalSpacekCz\Blog\Post $blogPost
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 */
	public function __construct(
		\MichalSpacekCz\Blog\Post $blogPost,
		\MichalSpacekCz\Formatter\Texy $texyFormatter
	)
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
	 * @return \MichalSpacekCz\Form\Blog\Post
	 */
	protected function createComponentAddPost(string $formName): \MichalSpacekCz\Form\Blog\Post
	{
		$form = new \MichalSpacekCz\Form\Blog\Post($this, $formName, $this->blogPost);
		$form->onSuccess[] = [$this, 'submittedAddpost'];
		return $form;
	}


	/**
	 * @param \MichalSpacekCz\Form\Blog\Post $form
	 * @param \Nette\Utils\ArrayHash $values
	 */
	public function submittedAddPost(\MichalSpacekCz\Form\Blog\Post $form, \Nette\Utils\ArrayHash $values): void
	{
		try {
			$post = new \MichalSpacekCz\Blog\Post\Data();
			$post->translationGroupId = (empty($values->translationGroup) ? null : $values->translationGroup);
			$post->localeId = (empty($values->locale) ? null : $values->locale);
			$post->slug = $values->slug;
			$post->titleTexy = $values->title;
			$post->leadTexy = (empty($values->lead) ? null : $values->lead);
			$post->textTexy = $values->text;
			$post->originallyTexy = (empty($values->originally) ? null : $values->originally);
			$post->published = new \DateTime($values->published);
			$post->previewKey = (empty($values->previewKey) ? null : $values->previewKey);
			$post->ogImage = (empty($values->ogImage) ? null : $values->ogImage);
			$post->tags = (empty($values->tags) ? null : $this->blogPost->tagsToArray($values->tags));
			$post->slugTags = (empty($values->tags) ? null : $this->blogPost->getSlugTags($values->tags));
			$post->recommended = (empty($values->recommended) ? null : \Nette\Utils\Json::decode($values->recommended));
			$post->twitterCard = (empty($values->twitterCard) ? null : $values->twitterCard);
			$this->blogPost->enrich($post);
			$this->blogPost->add($post);
			$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postadded', [$this->link('edit', [$post->postId]), $post->href]));
		} catch (\UnexpectedValueException $e) {
			$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.duplicateslug'), 'error');
		}
		$this->redirect('Blog:');
	}


	/**
	 * @param  string $param [description]
	 */
	public function actionEdit(string $param): void
	{
		$this->post = $this->blogPost->getById($param);
		if (!$this->post) {
			throw new \Nette\Application\BadRequestException("Post id {$param} does not exist, yet", \Nette\Http\Response::S404_NOT_FOUND);
		}

		$title = \Nette\Utils\Html::el()->setText('Příspěvek ')->addHtml($this->post->title);
		$this->template->pageTitle = strip_tags((string)$title);
		$this->template->pageHeader = $title;
		$this->template->postId = $this->post->postId;
	}


	/**
	 * @param string $formName
	 * @return \MichalSpacekCz\Form\Blog\Post
	 */
	protected function createComponentEditPost(string $formName): \MichalSpacekCz\Form\Blog\Post
	{
		$form = new \MichalSpacekCz\Form\Blog\Post($this, $formName, $this->blogPost);
		$form->setPost($this->post);
		$form->onSuccess[] = [$this, 'submittedEditPost'];
		return $form;
	}


	/**
	 * @param \MichalSpacekCz\Form\Blog\Post $form
	 * @param \Nette\Utils\ArrayHash $values
	 */
	public function submittedEditPost(\MichalSpacekCz\Form\Blog\Post $form, \Nette\Utils\ArrayHash $values): void
	{
		$post = new \MichalSpacekCz\Blog\Post\Data();
		$post->postId = $this->post->postId;
		$post->translationGroupId = (empty($values->translationGroup) ? null : $values->translationGroup);
		$post->localeId = (empty($values->locale) ? null : $values->locale);
		$post->slug = $values->slug;
		$post->titleTexy = $values->title;
		$post->leadTexy = (empty($values->lead) ? null : $values->lead);
		$post->textTexy = $values->text;
		$post->originallyTexy = (empty($values->originally) ? null : $values->originally);
		$post->published = new \DateTime($values->published);
		$post->previewKey = (empty($values->previewKey) ? null : $values->previewKey);
		$post->ogImage = (empty($values->ogImage) ? null : $values->ogImage);
		$post->tags = (empty($values->tags) ? []: $this->blogPost->tagsToArray($values->tags));
		$post->slugTags = (empty($values->tags) ? [] : $this->blogPost->getSlugTags($values->tags));
		$post->previousSlugTags = $this->post->slugTags;
		$post->recommended = (empty($values->recommended) ? null : \Nette\Utils\Json::decode($values->recommended));
		$post->twitterCard = (empty($values->twitterCard) ? null : $values->twitterCard);
		$post->editSummary = (empty($values->editSummary) ? null : $values->editSummary);
		$this->blogPost->enrich($post);
		$this->blogPost->update($post);
		$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postupdated', [$this->link('edit', [$post->postId]), $post->href]));
		$this->redirect('Blog:');
	}


	public function actionPreview(): void
	{
		if (!$this->isAjax()) {
			throw new \Nette\Application\BadRequestException('Not an AJAX call');
		}
		$this->texyFormatter->disableCache();
		$post = new \MichalSpacekCz\Blog\Post\Data();
		$post->slug = $this->request->getPost('slug');
		$post->title = $this->request->getPost('title');
		$post->leadTexy = (empty($this->request->getPost('lead')) ? null : $this->request->getPost('lead'));
		$post->textTexy = $this->request->getPost('text');
		$post->originallyTexy = (empty($this->request->getPost('originally')) ? null : $this->request->getPost('originally'));
		$post->published = new \DateTime($this->request->getPost('published'));
		$post->tags = (empty($this->request->getPost('tags')) ? null : $this->blogPost->tagsToArray($this->request->getPost('tags')));
		$post->recommended = (empty($this->request->getPost('recommended')) ? null : \Nette\Utils\Json::decode($this->request->getPost('recommended')));
		$this->blogPost->enrich($post);
		$preview = $this->createTemplate();
		$preview->setFile(__DIR__ . '/templates/Blog/preview.latte');
		$preview->post = $this->blogPost->format($post);
		$preview->edits = $this->blogPost->getEdits((int)$this->request->getPost('postId'));

		$this->payload->status = \Nette\Http\IResponse::S200_OK;
		$this->payload->statusMessage = 'Formatted';
		$this->payload->formatted = (string)$preview;
		$this->sendPayload();
	}

}
