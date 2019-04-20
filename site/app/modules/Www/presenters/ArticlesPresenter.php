<?php
namespace App\WwwModule\Presenters;

use MichalSpacekCz\Articles;

class ArticlesPresenter extends BasePresenter
{

	/** @var Articles */
	protected $articles;


	public function __construct(Articles $articles)
	{
		$this->articles = $articles;
		parent::__construct();
	}


	public function renderDefault()
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.articles');
		$this->template->articles  = $this->articles->getAll();
	}


}
