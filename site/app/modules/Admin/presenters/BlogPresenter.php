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
		$posts = $this->blogPost->getAll();
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
		$form = new \MichalSpacekCz\Form\Blog\Post($this, $formName);
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
			$this->blogPost->add($values->title, $values->slug, $values->text);
			$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postadded'));
		} catch (\UnexpectedValueException $e) {
			$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.duplicateslug'), 'error');
		}
		$this->redirect('Blog:default');
	}

}
