<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Articles;

use Contributte\Translation\Translator;
use MichalSpacekCz\Articles\Articles;
use MichalSpacekCz\Articles\ArticleSummary;
use MichalSpacekCz\Articles\ArticleSummaryFactory;
use MichalSpacekCz\Presentation\Www\BasePresenter;

final class ArticlesPresenter extends BasePresenter
{

	public function __construct(
		private readonly Articles $articles,
		private readonly ArticleSummaryFactory $articleSummaryFactory,
		private readonly Translator $translator,
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
