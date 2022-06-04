<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Resource;

class StringResource implements ResourceInterface
{

	public function __construct(
		private string $string,
	) {
	}


	public function getContent(): string
	{
		return $this->string;
	}


	public function getExtension(): ?string
	{
		return null;
	}

}
