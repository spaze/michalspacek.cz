<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTime;
use MichalSpacekCz\Articles\ArticleEdit;
use MichalSpacekCz\Articles\Components\ArticleWithId;
use MichalSpacekCz\Articles\Components\ArticleWithPublishTime;
use MichalSpacekCz\Articles\Components\ArticleWithSlug;
use MichalSpacekCz\Articles\Components\ArticleWithSummary;
use MichalSpacekCz\Articles\Components\ArticleWithTags;
use MichalSpacekCz\Articles\Components\ArticleWithTextAndEdits;
use MichalSpacekCz\Articles\Components\ArticleWithUpdateTime;
use MichalSpacekCz\Feed\ExportsOmittable;
use MichalSpacekCz\Twitter\TwitterCard;
use Nette\Utils\Html;
use Override;

final readonly class BlogPost implements ExportsOmittable, ArticleWithId, ArticleWithSlug, ArticleWithSummary, ArticleWithTextAndEdits, ArticleWithTags, ArticleWithUpdateTime, ArticleWithPublishTime
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
		private ?int $id,
		private string $slug,
		private int $localeId,
		private ?string $locale,
		private ?int $translationGroupId,
		private Html $title,
		private string $titleTexy,
		private ?Html $lead,
		private ?string $leadTexy,
		private Html $text,
		private string $textTexy,
		private ?DateTime $published,
		private bool $needsPreviewKey,
		private ?string $previewKey,
		private ?Html $originally,
		private ?string $originallyTexy,
		private ?string $ogImage,
		private array $tags,
		private array $slugTags,
		private array $recommended,
		private ?TwitterCard $twitterCard,
		private string $href,
		private array $edits,
		private array $cspSnippets,
		private array $allowedTagsGroups,
		private bool $omitExports,
	) {
	}


	#[Override]
	public function hasId(): bool
	{
		return $this->id !== null;
	}


	#[Override]
	public function getId(): ?int
	{
		return $this->id;
	}


	#[Override]
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


	#[Override]
	public function hasSummary(): bool
	{
		return $this->lead !== null;
	}


	#[Override]
	public function getSummary(): ?Html
	{
		return $this->lead;
	}


	public function getSummaryTexy(): ?string
	{
		return $this->leadTexy;
	}


	#[Override]
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


	#[Override]
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
	#[Override]
	public function getTags(): array
	{
		return $this->tags;
	}


	/**
	 * @return list<string>
	 */
	#[Override]
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


	#[Override]
	public function getUpdateTime(): ?DateTime
	{
		return $this->edits !== [] ? current($this->edits)->getEditedAt() : null;
	}


	public function getHref(): string
	{
		return $this->href;
	}


	/**
	 * @return list<ArticleEdit>
	 */
	#[Override]
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


	#[Override]
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
