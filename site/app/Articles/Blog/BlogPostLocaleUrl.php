<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTime;

class BlogPostLocaleUrl
{

	/**
	 * @param list<string> $slugTags
	 */
	public function __construct(
		private readonly ?string $locale,
		private readonly string $slug,
		private readonly ?DateTime $published,
		private readonly ?string $previewKey,
		private readonly array $slugTags,
	) {
	}


	public function getLocale(): ?string
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


	public function getPreviewKey(): ?string
	{
		return $this->published === null || $this->published > new DateTime() ? $this->previewKey : null;
	}


	/**
	 * @return list<string>
	 */
	public function getSlugTags(): array
	{
		return $this->slugTags;
	}

}
