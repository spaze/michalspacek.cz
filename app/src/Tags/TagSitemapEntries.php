<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tags;

use Contributte\Translation\Translator;
use MichalSpacekCz\Articles\Articles;
use MichalSpacekCz\Sitemap\SitemapEntries;
use MichalSpacekCz\Sitemap\SitemapEntry;
use Override;

/**
 * Every tag of the published blog posts in the current locale, with its translations.
 */
final readonly class TagSitemapEntries implements SitemapEntries
{

	public function __construct(
		private Articles $articles,
		private TagTranslations $tagTranslations,
		private Translator $translator,
	) {
	}


	#[Override]
	public function getEntries(): array
	{
		$locale = $this->translator->getDefaultLocale();
		$translations = $this->tagTranslations->getAllLocaleLinkParams()[$locale] ?? [];
		$entries = [];
		foreach (array_keys($this->articles->getAllTags()) as $tag) {
			$entries[] = new SitemapEntry($translations[$tag] ?? [$locale => ['tag' => $tag]]);
		}
		return $entries;
	}

}
