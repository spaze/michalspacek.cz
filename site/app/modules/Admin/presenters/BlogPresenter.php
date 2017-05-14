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

	/** @var \Nette\Application\LinkGenerator */
	protected $linkGenerator;

	/** @var \MichalSpacekCz\Application\LocaleLinkGenerator */
	protected $localeLinkGenerator;

	/** @var \MichalSpacekCz\Blog\Post\Data */
	private $post;


	/**
	 * @param \MichalSpacekCz\Blog\Post $blogPost
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \Nette\Application\LinkGenerator $linkGenerator
	 * @param \MichalSpacekCz\Application\LocaleLinkGenerator $localeLinkGenerator
	 */
	public function __construct(
		\MichalSpacekCz\Blog\Post $blogPost,
		\MichalSpacekCz\Formatter\Texy $texyFormatter,
		\Nette\Application\LinkGenerator $linkGenerator,
		\MichalSpacekCz\Application\LocaleLinkGenerator $localeLinkGenerator
	)
	{
		$this->blogPost = $blogPost;
		$this->texyFormatter = $texyFormatter;
		$this->linkGenerator = $linkGenerator;
		$this->localeLinkGenerator = $localeLinkGenerator;
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$posts = [];
		foreach ($this->blogPost->getAll() as $post) {
			$params = [
				'slug' => $post->slug,
				'preview' => ($post->needsPreviewKey() ? $post->previewKey : null),
			];
			if ($post->locale === null || $post->locale === $this->translator->getDefaultLocale()) {
				$post->href = $this->linkGenerator->link('Blog:Post:', $params);
			} else {
				$links = $this->localeLinkGenerator->links('Blog:Post:', $this->localeLinkGenerator->defaultParams($params));
				$post->href = $links[$post->locale];
			}
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
			$post->locale = (empty($values->locale) ? null : $values->locale);
			$post->published = new \DateTime($values->published);
			$post->previewKey = (empty($values->previewKey) ? null : $values->previewKey);
			$post->slug = $values->slug;
			$post->titleTexy = $values->title;
			$post->leadTexy = (empty($values->lead) ? null : $values->lead);
			$post->textTexy = $values->text;
			$post->originallyTexy = (empty($this->request->getPost('originally')) ? null : $this->request->getPost('originally'));
			$post->ogImage = (empty($this->request->getPost('ogImage')) ? null : $this->request->getPost('ogImage'));
			$post->tags = (empty($this->request->getPost('tags')) ? null : $this->tagsToArray($this->request->getPost('tags')));
			$post->recommended = (empty($this->request->getPost('recommended')) ? null : $this->request->getPost('recommended'));
			$post->twitterCard = (empty($this->request->getPost('twitterCard')) ? null : $this->request->getPost('twitterCard'));
			$this->blogPost->add($post);
			$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postadded'));
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
		$post->locale = (empty($values->locale) ? null : $values->locale);
		$post->published = new \DateTime($values->published);
		$post->previewKey = (empty($values->previewKey) ? null : $values->previewKey);
		$post->slug = $values->slug;
		$post->titleTexy = $values->title;
		$post->leadTexy = (empty($values->lead) ? null : $values->lead);
		$post->textTexy = $values->text;
		$post->originallyTexy = (empty($values->originally) ? null : $values->originally);
		$post->ogImage = (empty($values->ogImage) ? null : $values->ogImage);
		$post->tags = (empty($values->tags) ? null : $this->tagsToArray($values->tags));
		$post->recommended = (empty($values->recommended) ? null : $values->recommended);
		$post->twitterCard = (empty($values->twitterCard) ? null : $values->twitterCard);
		$post->editSummary = (empty($values->editSummary) ? null : $values->editSummary);

		$this->blogPost->update($post);
		$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postupdated'));
		$this->redirect('Blog:');
	}


	public function actionPreview(): void
	{
		if (!$this->isAjax()) {
			throw new \Nette\Application\BadRequestException('Not an AJAX call');
		}
		$this->texyFormatter->disableCache();

		$post = new \MichalSpacekCz\Blog\Post\Data();
		$post->published = new \DateTime($this->request->getPost('published'));
		$post->titleTexy = $this->request->getPost('title');
		$post->leadTexy = (empty($this->request->getPost('lead')) ? null : $this->request->getPost('lead'));
		$post->textTexy = $this->request->getPost('text');
		$post->originallyTexy = (empty($this->request->getPost('originally')) ? null : $this->request->getPost('originally'));
		$post->tags = (empty($this->request->getPost('tags')) ? null : $this->tagsToArray($this->request->getPost('tags')));
		$post->recommended = (empty($this->request->getPost('recommended')) ? null : $this->request->getPost('recommended'));
		$preview = $this->createTemplate();
		$preview->setFile(__DIR__ . '/templates/Blog/preview.latte');
		$preview->post = $this->blogPost->format($post);
		$preview->edits = $this->blogPost->getEdits((int)$this->request->getPost('postId'));

		$this->payload->status = \Nette\Http\IResponse::S200_OK;
		$this->payload->statusMessage = 'Formatted';
		$this->payload->formatted = (string)$preview;
		$this->sendPayload();
	}


	/**
	 * Convert tags string to JSON.
	 *
	 * @param string $tags
	 * @return string[]
	 */
	private function tagsToArray(string $tags): array
	{
		return array_filter(preg_split('/\s*,\s*/', $tags));
	}

}
