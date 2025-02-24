<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media;

final class Video
{

	private bool $lazyLoad = false;


	public function __construct(
		private readonly ?string $videoHref,
		private readonly ?string $thumbnailFilename,
		private readonly ?string $thumbnailUrl,
		private readonly ?string $thumbnailAlternativeFilename,
		private readonly ?string $thumbnailAlternativeUrl,
		private readonly ?string $thumbnailAlternativeContentType,
		private readonly int $thumbnailWidth,
		private readonly int $thumbnailHeight,
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


	public function getThumbnailUrl(): ?string
	{
		return $this->thumbnailUrl;
	}


	public function getThumbnailAlternativeFilename(): ?string
	{
		return $this->thumbnailAlternativeFilename;
	}


	public function getThumbnailAlternativeUrl(): ?string
	{
		return $this->thumbnailAlternativeUrl;
	}


	public function getThumbnailAlternativeContentType(): ?string
	{
		return $this->thumbnailAlternativeContentType;
	}


	public function getThumbnailWidth(): int
	{
		return $this->thumbnailWidth;
	}


	public function getThumbnailHeight(): int
	{
		return $this->thumbnailHeight;
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
