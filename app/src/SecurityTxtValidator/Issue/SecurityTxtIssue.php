<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Issue;

use Nette\Utils\Html;

final readonly class SecurityTxtIssue
{

	public function __construct(
		private Html $message,
		private Html $howToFix,
		private ?string $correctValue,
		private ?string $specSection,
	) {
	}


	public function getMessage(): Html
	{
		return $this->message;
	}


	public function getHowToFix(): Html
	{
		return $this->howToFix;
	}


	public function getCorrectValue(): ?string
	{
		return $this->correctValue;
	}


	public function getSpecSection(): ?string
	{
		return $this->specSection;
	}

}
