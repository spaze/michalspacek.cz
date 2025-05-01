<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

final readonly class LambdaFunctions
{

	public function __construct(private string $stage)
	{
	}


	public function getFetch(): string
	{
		return "security-txt-{$this->stage}-fetch";
	}


	public function getVersion(): string
	{
		return "security-txt-{$this->stage}-version";
	}

}
