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

	public ?int $postId;

	public string $slug;

	public int $localeId;

	public ?int $translationGroupId;

	public ?string $locale;

	/** @var Html<Html|string> */
	public Html $title;

	public string $titleTexy;

	/** @var Html<Html|string>|null */
	public ?Html $lead;

	public ?string $leadTexy;

	/** @var Html<Html|string> */
	public Html $text;

	public string $textTexy;

	public ?DateTime $published;

	public ?string $previewKey;

	/** @var Html<Html|string>|null */
	public ?Html $originally;

	public ?string $originallyTexy;

	public ?string $ogImage;

	/** @var list<string> */
	public array $tags = [];

	/** @var list<string> */
	public array $slugTags = [];

	/** @var array<int, string> */
	public array $previousSlugTags = [];

	/** @var array<int, BlogPostRecommendedLink> */
	public array $recommended;

	public ?TwitterCard $twitterCard;

	public string $href;

	public ?string $editSummary;

	/** @var list<ArticleEdit> */
	public array $edits = [];

	/** @var list<string> */
	public array $cspSnippets = [];

	/** @var list<string> */
	public array $allowedTags = [];

	public bool $omitExports;


	public function needsPreviewKey(DateTime $when = new DateTime()): bool
	{
		return $this->published === null || $this->published > $when;
	}


	public function omitExports(): bool
	{
		return $this->omitExports;
	}


	public function hasId(): bool
	{
		return $this->postId !== null;
	}


	public function getId(): ?int
	{
		return $this->postId;
	}


	public function getSlug(): string
	{
		return $this->slug;
	}


	public function hasSummary(): bool
	{
		return $this->lead !== null;
	}


	public function getSummary(): ?Html
	{
		return $this->lead;
	}


	public function getText(): Html
	{
		return $this->text;
	}


	public function getTags(): array
	{
		return $this->tags;
	}


	public function getSlugTags(): array
	{
		return $this->slugTags;
	}


	public function getUpdateTime(): ?DateTime
	{
		return $this->edits ? current($this->edits)->editedAt : null;
	}


	public function getPublishTime(): ?DateTime
	{
		return $this->published;
	}


	/**
	 * @return list<ArticleEdit>
	 */
	public function getEdits(): array
	{
		return $this->edits;
	}

}
