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

	/** @var \Nette\Database\Row */
	private $post;


	/**
	 * @param \MichalSpacekCz\Blog\Post $blogPost
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 */
	public function __construct(\MichalSpacekCz\Blog\Post $blogPost, \MichalSpacekCz\Formatter\Texy $texyFormatter)
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
			$this->blogPost->add($values->title, $values->slug, $values->lead, $values->text, $values->published, $values->originally, $values->twitterCard, $values->ogImage);
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
	}


	/**
	 * @param string $formName
	 * @return \MichalSpacekCz\Form\Blog\Post
	 */
	protected function createComponentEditPost(string $formName): \MichalSpacekCz\Form\Blog\Post
	{
		$form = new \MichalSpacekCz\Form\Blog\Post($this, $formName, $this->blogPost);
		$form->setPost($this->blogPost->getById($this->post->postId));
		$form->onSuccess[] = [$this, 'submittedEditPost'];
		return $form;
	}


	/**
	 * @param \MichalSpacekCz\Form\Blog\Post $form
	 * @param \Nette\Utils\ArrayHash $values
	 */
	public function submittedEditPost(\MichalSpacekCz\Form\Blog\Post $form, \Nette\Utils\ArrayHash $values): void
	{
		$this->blogPost->update($this->post->postId, $values->title, $values->slug, $values->lead, $values->text, $values->published, $values->originally, $values->twitterCard, $values->ogImage);
		$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postupdated'));
		$this->redirect('Blog:');
	}

}
