<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles;

use DateTime;
use Nette\Utils\Html;

class ArticleEdit
{

	public function __construct(
		private readonly DateTime $editedAt,
		private readonly Html $summary,
		private readonly string $summaryTexy,
	) {
	}


	public function getEditedAt(): DateTime
	{
		return $this->editedAt;
	}


	public function getSummary(): Html
	{
		return $this->summary;
	}


	public function getSummaryTexy(): string
	{
		return $this->summaryTexy;
	}

}
