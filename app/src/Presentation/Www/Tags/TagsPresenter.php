<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Tags;

use Contributte\Translation\Translator;
use MichalSpacekCz\Articles\Articles;
use MichalSpacekCz\Articles\ArticleSummary;
use MichalSpacekCz\Articles\ArticleSummaryFactory;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\Sitemap\InSitemap;
use MichalSpacekCz\Tags\TagSitemapEntries;
use MichalSpacekCz\Tags\TagTranslations;
use MichalSpacekCz\Utils\Strings;
use Nette\Application\BadRequestException;
use Override;

final class TagsPresenter extends BasePresenter
{

	/** @var array<string, array<string, string>>|null null on the list of tags, which exists in every language */
	private ?array $localeLinkParams = null;


	public function __construct(
		private readonly Articles $articles,
		private readonly Strings $strings,
		private readonly TexyFormatter $texyFormatter,
		private readonly TagTranslations $tagTranslations,
		private readonly ArticleSummaryFactory $articleSummaryFactory,
		private readonly Translator $translator,
	) {
		parent::__construct();
	}


	#[InSitemap]
	public function actionDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.label.tags');
		$tags = [];
		foreach ($this->articles->getAllTags() as $slug => $tag) {
			$tags[$this->strings->getInitialLetterUppercase($tag)][$slug] = $tag;
		}
		$this->template->allTags = $tags;
	}


	#[InSitemap(TagSitemapEntries::class)]
	public function actionTag(string $tag): void
	{
		$label = $this->articles->getLabelByTag($tag);
		if ($label === null) {
			throw new BadRequestException('Unknown tag');
		}

		$articles = $this->articles->getAllByTags([$tag]);
		$this->localeLinkParams = $this->tagTranslations->getLocaleLinkParams($tag);

		$this->template->pageTitle = $this->texyFormatter->translatePossiblyUnsafeHtml('messages.label.articlesbytag', [$label]);
		$this->template->articles = $articles;
	}


	protected function createComponentArticleSummary(): ArticleSummary
	{
		return $this->articleSummaryFactory->create();
	}


	/**
	 * The language switcher leads to the list of tags when the tag isn't in the posts of the other language.
	 */
	#[Override]
	protected function getLocaleLinksGeneratorDestination(): string
	{
		return ($this->localeLinkParams === null || count($this->localeLinkParams) > 1 ? parent::getLocaleLinksGeneratorDestination() : 'Www:Tags:');
	}


	/**
	 * Translated locale parameters for a tag, the default ones for the list of tags.
	 *
	 * @return array<string, array<array-key, mixed>>
	 */
	#[Override]
	protected function getLocaleLinkParams(): array
	{
		return $this->localeLinkParams ?? parent::getLocaleLinkParams();
	}

}
