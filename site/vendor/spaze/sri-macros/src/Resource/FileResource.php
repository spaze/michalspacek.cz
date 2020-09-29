<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Resource;

use Spaze\SubresourceIntegrity\Exceptions;

class FileResource implements ResourceInterface
{

	/** @var string */
	private $filename;


	public function __construct(string $filename)
	{
		$this->filename = $filename;
	}


	public function getContent(): string
	{
		$content = file_get_contents($this->filename);
		if (!$content) {
			throw new Exceptions\CannotGetFileContentException();
		}
		return $content;
	}


	public function getExtension(): ?string
	{
		return pathinfo($this->filename, PATHINFO_EXTENSION);
	}

}
