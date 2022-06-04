<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Resource;

use Spaze\SubresourceIntegrity\Exceptions\CannotGetFileContentException;

class FileResource implements ResourceInterface
{

	public function __construct(
		private string $filename,
	) {
	}


	/**
	 * @throws CannotGetFileContentException
	 */
	public function getContent(): string
	{
		$content = file_get_contents($this->filename);
		if (!$content) {
			throw new CannotGetFileContentException($this->filename);
		}
		return $content;
	}


	public function getExtension(): ?string
	{
		return pathinfo($this->filename, PATHINFO_EXTENSION);
	}

}
