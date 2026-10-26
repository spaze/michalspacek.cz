<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

use Nette\Utils\Html;

final readonly class Photo
{

	/**
	 * @param list<PhotoFile> $sizes
	 */
	public function __construct(
		private string $title,
		private PhotoFile $file,
		private string|Html $description,
		private array $sizes,
	) {
	}


	public function getTitle(): string
	{
		return $this->title;
	}


	public function getFile(): PhotoFile
	{
		return $this->file;
	}


	public function getDescription(): string|Html
	{
		return $this->description;
	}


	/**
	 * @return list<PhotoFile>
	 */
	public function getSizes(): array
	{
		return $this->sizes;
	}

}
