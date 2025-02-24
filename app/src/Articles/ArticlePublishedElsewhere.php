<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles;

use DateTime;
use MichalSpacekCz\Articles\Components\ArticleWithId;
use MichalSpacekCz\Articles\Components\ArticleWithPublishTime;
use MichalSpacekCz\Articles\Components\ArticleWithSummary;
use Nette\Utils\Html;
use Override;

final readonly class ArticlePublishedElsewhere implements ArticleWithId, ArticleWithSummary, ArticleWithPublishTime
{

	public function __construct(
		private int $articleId,
		private Html $title,
		private string $titleTexy,
		private string $href,
		private DateTime $published,
		private Html $excerpt,
		private string $excerptTexy,
		private string $sourceName,
		private string $sourceHref,
	) {
	}


	#[Override]
	public function hasId(): bool
	{
		return true;
	}


	#[Override]
	public function getId(): ?int
	{
		return $this->articleId;
	}


	public function getTitle(): Html
	{
		return $this->title;
	}


	public function getTitleTexy(): string
	{
		return $this->titleTexy;
	}


	public function getHref(): string
	{
		return $this->href;
	}


	#[Override]
	public function hasSummary(): bool
	{
		return true;
	}


	#[Override]
	public function getSummary(): ?Html
	{
		return $this->excerpt;
	}


	public function getSummaryTexy(): string
	{
		return $this->excerptTexy;
	}


	#[Override]
	public function getPublishTime(): ?DateTime
	{
		return $this->published;
	}


	public function getSourceName(): string
	{
		return $this->sourceName;
	}


	public function getSourceHref(): string
	{
		return $this->sourceHref;
	}

}
