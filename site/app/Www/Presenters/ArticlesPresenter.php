<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Articles\Articles;

class ArticlesPresenter extends BasePresenter
{

	private Articles $articles;


	public function __construct(Articles $articles)
	{
		$this->articles = $articles;
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.articles');
		$this->template->articles  = $this->articles->getAll();
	}

}
