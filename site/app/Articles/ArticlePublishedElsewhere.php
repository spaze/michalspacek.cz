<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles;

use DateTime;
use MichalSpacekCz\Articles\Components\ArticleWithId;
use MichalSpacekCz\Articles\Components\ArticleWithPublishTime;
use MichalSpacekCz\Articles\Components\ArticleWithSummary;
use Nette\Utils\Html;

class ArticlePublishedElsewhere implements ArticleWithId, ArticleWithSummary, ArticleWithPublishTime
{

	public function __construct(
		private readonly int $articleId,
		private readonly Html $title,
		private readonly string $titleTexy,
		private readonly string $href,
		private readonly DateTime $published,
		private readonly Html $excerpt,
		private readonly string $excerptTexy,
		private readonly string $sourceName,
		private readonly string $sourceHref,
	) {
	}


	public function hasId(): bool
	{
		return true;
	}


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


	public function hasSummary(): bool
	{
		return true;
	}


	public function getSummary(): ?Html
	{
		return $this->excerpt;
	}


	public function getSummaryTexy(): string
	{
		return $this->excerptTexy;
	}


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
