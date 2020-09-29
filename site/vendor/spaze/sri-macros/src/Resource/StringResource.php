<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Resource;

class StringResource implements ResourceInterface
{

	/** @var string */
	private $string;


	public function __construct(string $string)
	{
		$this->string = $string;
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
