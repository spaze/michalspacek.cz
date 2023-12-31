<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

use Nette\Utils\Html;

readonly class Photo
{

	/**
	 * @param array<string, string> $sizes
	 */
	public function __construct(
		private string $title,
		private string $file,
		private string|Html $description,
		private array $sizes,
	) {
	}


	public function getTitle(): string
	{
		return $this->title;
	}


	public function getFile(): string
	{
		return $this->file;
	}


	public function getDescription(): string|Html
	{
		return $this->description;
	}


	/**
	 * @return array<string, string>
	 */
	public function getSizes(): array
	{
		return $this->sizes;
	}

}
