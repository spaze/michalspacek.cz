<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media\Resources;

abstract class MediaResources
{

	abstract protected function getSubDir(): string;


	/**
	 * @param string $imagesRoot Slides & other images root, just directory no FQDN, no leading slash, no trailing slash.
	 * @param string $staticRoot Static files root FQDN, no trailing slash.
	 * @param string $locationRoot Physical location root directory, no trailing slash.
	 */
	public function __construct(
		protected readonly string $imagesRoot,
		protected readonly string $staticRoot,
		protected readonly string $locationRoot,
	) {
	}


	public function getImageFilename(int $id, string $fileName): string
	{
		return "{$this->locationRoot}/{$this->imagesRoot}/{$this->getSubDir()}/{$id}/{$fileName}";
	}


	public function getImageUrl(int $id, string $fileName): string
	{
		return "{$this->staticRoot}/{$this->imagesRoot}/{$this->getSubDir()}/{$id}/{$fileName}";
	}

}
