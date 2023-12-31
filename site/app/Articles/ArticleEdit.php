<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles;

use DateTime;
use Nette\Utils\Html;

readonly class ArticleEdit
{

	public function __construct(
		private DateTime $editedAt,
		private Html $summary,
		private string $summaryTexy,
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
