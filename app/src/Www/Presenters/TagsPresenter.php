<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use Contributte\Translation\Translator;
use MichalSpacekCz\Articles\Articles;
use MichalSpacekCz\Articles\ArticleSummary;
use MichalSpacekCz\Articles\ArticleSummaryFactory;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Tags\Tags;
use MichalSpacekCz\Utils\Strings;
use Nette\Application\BadRequestException;
use Override;

final class TagsPresenter extends BasePresenter
{

	/** @var array<string, array<string, string>> */
	private array $localeLinkParams = [];


	public function __construct(
		private readonly Articles $articles,
		private readonly Strings $strings,
		private readonly TexyFormatter $texyFormatter,
		private readonly Tags $tags,
		private readonly ArticleSummaryFactory $articleSummaryFactory,
		private readonly Translator $translator,
	) {
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.label.tags');
		$tags = [];
		foreach ($this->articles->getAllTags() as $slug => $tag) {
			$tags[$this->strings->getInitialLetterUppercase($tag)][$slug] = $tag;
		}
		$this->template->allTags = $tags;
	}


	public function actionTag(string $tag): void
	{
		$label = $this->articles->getLabelByTag($tag);
		if ($label === null) {
			throw new BadRequestException('Unknown tag');
		}

		$articles = $this->articles->getAllByTags([$tag]);
		$this->localeLinkParams = $this->tags->findLocaleLinkParams($articles, $tag);

		$this->template->pageTitle = $this->texyFormatter->translate('messages.label.articlesbytag', [$label]);
		$this->template->articles = $articles;
	}


	protected function createComponentArticleSummary(): ArticleSummary
	{
		return $this->articleSummaryFactory->create();
	}


	/**
	 * Get original module:presenter:action for locale links.
	 */
	#[Override]
	protected function getLocaleLinkAction(): string
	{
		return (count($this->localeLinkParams) > 1 ? parent::getLocaleLinkAction() : 'Www:Tags:');
	}


	/**
	 * Translated locale parameters for tags.
	 *
	 * @return array<string, array<string, string>>
	 */
	#[Override]
	protected function getLocaleLinkParams(): array
	{
		return $this->localeLinkParams;
	}

}
