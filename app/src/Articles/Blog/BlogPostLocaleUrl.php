<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTime;

final readonly class BlogPostLocaleUrl
{

	/**
	 * @param list<string> $slugTags
	 */
	public function __construct(
		private string $locale,
		private string $slug,
		private ?DateTime $published,
		private bool $isPublished,
		private ?string $previewKey,
		private array $slugTags,
	) {
	}


	public function getLocale(): string
	{
		return $this->locale;
	}


	public function getSlug(): string
	{
		return $this->slug;
	}


	public function getPublished(): ?DateTime
	{
		return $this->published;
	}


	/**
	 * Whether it was out when the URLs were loaded.
	 */
	public function isPublished(): bool
	{
		return $this->isPublished;
	}


	public function getPreviewKey(): ?string
	{
		return $this->isPublished() ? null : $this->previewKey;
	}


	/**
	 * @return list<string>
	 */
	public function getSlugTags(): array
	{
		return $this->slugTags;
	}

}
