<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

class VideoThumbnail
{

	private bool $lazyLoad = false;


	public function __construct(
		private readonly ?string $videoHref,
		private readonly ?string $thumbnailFilename,
		private readonly ?string $url,
		private readonly ?string $thumbnailAlternativeFilename,
		private readonly ?string $alternativeUrl,
		private readonly ?string $alternativeContentType,
		private readonly int $width,
		private readonly int $height,
		private readonly ?string $videoPlatform,
	) {
	}


	public function getVideoHref(): ?string
	{
		return $this->videoHref;
	}


	public function getThumbnailFilename(): ?string
	{
		return $this->thumbnailFilename;
	}


	public function getUrl(): ?string
	{
		return $this->url;
	}


	public function getThumbnailAlternativeFilename(): ?string
	{
		return $this->thumbnailAlternativeFilename;
	}


	public function getAlternativeUrl(): ?string
	{
		return $this->alternativeUrl;
	}


	public function getAlternativeContentType(): ?string
	{
		return $this->alternativeContentType;
	}


	public function getWidth(): int
	{
		return $this->width;
	}


	public function getHeight(): int
	{
		return $this->height;
	}


	public function getVideoPlatform(): ?string
	{
		return $this->videoPlatform;
	}


	public function setLazyLoad(bool $lazyLoad): self
	{
		$this->lazyLoad = $lazyLoad;
		return $this;
	}


	public function isLazyLoad(): bool
	{
		return $this->lazyLoad;
	}

}
