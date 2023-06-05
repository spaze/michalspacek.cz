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

	public int $articleId;

	/** @var Html<Html|string> */
	public Html $title;

	public string $titleTexy;

	public string $href;

	public DateTime $published;

	/** @var Html<Html|string> */
	public Html $excerpt;

	public string $excerptTexy;

	public string $sourceName;

	public string $sourceHref;


	public function hasId(): bool
	{
		return true;
	}


	public function getId(): ?int
	{
		return $this->articleId;
	}


	public function hasSummary(): bool
	{
		return true;
	}


	public function getSummary(): ?Html
	{
		return $this->excerpt;
	}


	public function getPublishTime(): ?DateTime
	{
		return $this->published;
	}

}
