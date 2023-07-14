<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Articles\Articles;
use MichalSpacekCz\Articles\ArticleSummary;
use MichalSpacekCz\Articles\ArticleSummaryFactory;

class ArticlesPresenter extends BasePresenter
{

	public function __construct(
		private readonly Articles $articles,
		private readonly ArticleSummaryFactory $articleSummaryFactory,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.articles');
		$this->template->articles = $this->articles->getAll();
	}


	protected function createComponentArticleSummary(): ArticleSummary
	{
		return $this->articleSummaryFactory->create();
	}

}
