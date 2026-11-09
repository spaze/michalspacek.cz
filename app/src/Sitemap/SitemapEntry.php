<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Sitemap;

use DateTimeInterface;

/**
 * One page in every locale it exists in, so a translated page is one entry, not one per locale.
 */
final readonly class SitemapEntry
{

	/**
	 * @param array<string, array<array-key, mixed>> $localeParams locale => the action's parameters, only for the locales the page exists in, so a page without a translation has no alternate
	 * @param array<string, DateTimeInterface> $lastModified locale => when the page last changed in that locale, for the locales where that's known
	 */
	public function __construct(
		public array $localeParams,
		public array $lastModified = [],
	) {
	}

}
