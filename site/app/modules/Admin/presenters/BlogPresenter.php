<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use \Nette\Utils\Html;
use \Nette\Utils\Json;

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
			$this->blogPost->add($values->title, $values->slug, $values->lead, $values->text, $values->published, $values->originally, $values->twitterCard, $values->ogImage, $this->tagsToArray($values->tags), $values->recommended);
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

		$title = Html::el()->setText('Příspěvek ')->addHtml($this->post->title);
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
		$this->blogPost->update($this->post->postId, $values->title, $values->slug, $values->lead, $values->text, $values->published, $values->originally, $values->twitterCard, $values->ogImage, $this->tagsToArray($values->tags), $values->recommended);
		$this->flashMessage($this->texyFormatter->translate('messages.blog.admin.postupdated'));
		$this->redirect('Blog:');
	}


	public function actionPreview(): void
	{
		if (!$this->isAjax()) {
			throw new \Nette\Application\BadRequestException('Not an AJAX call');
		}

		$this->payload->status = \Nette\Http\IResponse::S200_OK;
		$this->payload->statusMessage = 'Formatted';
		$this->payload->formatted = Html::el('em')->setHtml($this->texyFormatter->noCache()->formatBlock($this->request->getPost('lead')));
		$this->payload->formatted .= $this->texyFormatter->noCache()->formatBlock($this->request->getPost('text'));
		$this->payload->formatted .= $this->texyFormatter->noCache()->formatBlock($this->request->getPost('originally'));
		if ($this->request->getPost('recommended')) {
			$list = Html::el('ul');
			foreach (Json::decode($this->request->getPost('recommended')) as $item) {
				$list->addHtml(Html::el('li')->setHtml($this->texyFormatter->noCache()->translate($item->text, [$item->url])));
			}
			$this->payload->formatted .= Html::el('hr');
			$this->payload->formatted .= Html::el('h3')->setHtml($this->texyFormatter->noCache()->translate('messages.blog.post.recommendedreading'));
			$this->payload->formatted .= $list;
		}
		$this->sendPayload();
	}


	/**
	 * Convert tags string to array.
	 *
	 * @param string $tags
	 * @return string[]
	 */
	private function tagsToArray(string $tags): array
	{
		return array_filter(preg_split('/\s*,\s*/', $tags));
	}

}
