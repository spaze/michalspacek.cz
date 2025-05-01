<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Issue;

final readonly class SecurityTxtLineIssue
{

	public function __construct(
		private SecurityTxtIssueLevel $level,
		private SecurityTxtIssue $issue,
		private string $lineContents,
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


	public function getLineContents(): string
	{
		return $this->lineContents;
	}

}
