<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Issue;

use Nette\Utils\Html;

final readonly class SecurityTxtLineIssue
{

	public function __construct(
		private SecurityTxtIssueLevel $level,
		private SecurityTxtIssue $issue,
		private Html $lineContents,
	) {
	}


	public function getLevel(): SecurityTxtIssueLevel
	{
		return $this->level;
	}


	public function getIssue(): SecurityTxtIssue
	{
		return $this->issue;
	}


	public function getLineContents(): Html
	{
		return $this->lineContents;
	}

}
