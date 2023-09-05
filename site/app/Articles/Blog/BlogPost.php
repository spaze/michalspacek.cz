<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTime;
use MichalSpacekCz\Articles\ArticleEdit;
use MichalSpacekCz\Articles\Components\ArticleWithEdits;
use MichalSpacekCz\Articles\Components\ArticleWithId;
use MichalSpacekCz\Articles\Components\ArticleWithPublishTime;
use MichalSpacekCz\Articles\Components\ArticleWithSlug;
use MichalSpacekCz\Articles\Components\ArticleWithSummary;
use MichalSpacekCz\Articles\Components\ArticleWithTags;
use MichalSpacekCz\Articles\Components\ArticleWithText;
use MichalSpacekCz\Articles\Components\ArticleWithUpdateTime;
use MichalSpacekCz\Feed\ExportsOmittable;
use MichalSpacekCz\Twitter\TwitterCard;
use Nette\Utils\Html;

class BlogPost implements ExportsOmittable, ArticleWithId, ArticleWithSlug, ArticleWithSummary, ArticleWithText, ArticleWithTags, ArticleWithUpdateTime, ArticleWithPublishTime, ArticleWithEdits
{

	/**
	 * @param list<string> $tags
	 * @param list<string> $slugTags
	 * @param list<BlogPostRecommendedLink> $recommended
	 * @param list<ArticleEdit> $edits
	 * @param list<string> $cspSnippets
	 * @param list<string> $allowedTagsGroups
	 */
	public function __construct(
		private readonly ?int $id,
		private readonly string $slug,
		private readonly int $localeId,
		private readonly ?string $locale,
		private readonly ?int $translationGroupId,
		private readonly Html $title,
		private readonly string $titleTexy,
		private readonly ?Html $lead,
		private readonly ?string $leadTexy,
		private readonly Html $text,
		private readonly string $textTexy,
		private readonly ?DateTime $published,
		private readonly bool $needsPreviewKey,
		private readonly ?string $previewKey,
		private readonly ?Html $originally,
		private readonly ?string $originallyTexy,
		private readonly ?string $ogImage,
		private readonly array $tags,
		private readonly array $slugTags,
		private readonly array $recommended,
		private readonly ?TwitterCard $twitterCard,
		private readonly string $href,
		private readonly array $edits,
		private readonly array $cspSnippets,
		private readonly array $allowedTagsGroups,
		private readonly bool $omitExports,
	) {
	}


	public function hasId(): bool
	{
		return $this->id !== null;
	}


	public function getId(): ?int
	{
		return $this->id;
	}


	public function getSlug(): string
	{
		return $this->slug;
	}


	public function getLocaleId(): int
	{
		return $this->localeId;
	}


	public function getLocale(): ?string
	{
		return $this->locale;
	}


	public function getTranslationGroupId(): ?int
	{
		return $this->translationGroupId;
	}


	public function getTitle(): Html
	{
		return $this->title;
	}


	public function getTitleTexy(): string
	{
		return $this->titleTexy;
	}


	public function hasSummary(): bool
	{
		return $this->lead !== null;
	}


	public function getSummary(): ?Html
	{
		return $this->lead;
	}


	public function getSummaryTexy(): ?string
	{
		return $this->leadTexy;
	}


	public function getText(): Html
	{
		return $this->text;
	}


	public function getTextTexy(): string
	{
		return $this->textTexy;
	}


	public function getPreviewKey(): ?string
	{
		return $this->previewKey;
	}


	public function needsPreviewKey(): bool
	{
		return $this->needsPreviewKey;
	}


	public function getPublishTime(): ?DateTime
	{
		return $this->published;
	}


	public function getOriginally(): ?Html
	{
		return $this->originally;
	}


	public function getOriginallyTexy(): ?string
	{
		return $this->originallyTexy;
	}


	public function getOgImage(): ?string
	{
		return $this->ogImage;
	}


	/**
	 * @return list<string>
	 */
	public function getTags(): array
	{
		return $this->tags;
	}


	/**
	 * @return list<string>
	 */
	public function getSlugTags(): array
	{
		return $this->slugTags;
	}


	/**
	 * @return list<BlogPostRecommendedLink>
	 */
	public function getRecommended(): array
	{
		return $this->recommended;
	}


	public function getTwitterCard(): ?TwitterCard
	{
		return $this->twitterCard;
	}


	public function getUpdateTime(): ?DateTime
	{
		return $this->edits ? current($this->edits)->editedAt : null;
	}


	public function getHref(): string
	{
		return $this->href;
	}


	/**
	 * @return list<ArticleEdit>
	 */
	public function getEdits(): array
	{
		return $this->edits;
	}


	/**
	 * @return list<string>
	 */
	public function getCspSnippets(): array
	{
		return $this->cspSnippets;
	}


	/**
	 * @return list<string>
	 */
	public function getAllowedTagsGroups(): array
	{
		return $this->allowedTagsGroups;
	}


	public function omitExports(): bool
	{
		return $this->omitExports;
	}


	public function withId(int $id): BlogPost
	{
		return new self(
			$id,
			$this->slug,
			$this->localeId,
			$this->locale,
			$this->translationGroupId,
			$this->title,
			$this->titleTexy,
			$this->lead,
			$this->leadTexy,
			$this->text,
			$this->textTexy,
			$this->published,
			$this->needsPreviewKey,
			$this->previewKey,
			$this->originally,
			$this->originallyTexy,
			$this->ogImage,
			$this->tags,
			$this->slugTags,
			$this->recommended,
			$this->twitterCard,
			$this->href,
			$this->edits,
			$this->cspSnippets,
			$this->allowedTagsGroups,
			$this->omitExports,
		);
	}

}
