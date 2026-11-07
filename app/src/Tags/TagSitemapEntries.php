<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tags;

use Contributte\Translation\Translator;
use MichalSpacekCz\Articles\Articles;
use MichalSpacekCz\Sitemap\SitemapEntries;
use MichalSpacekCz\Sitemap\SitemapEntry;
use Override;

/**
 * Every tag of the published blog posts in the current locale. A tag page has no alternate: the translation of a tag
 * is taken from a post that has it, by its position among the post's tags, and the translated tag's page may take its
 * own from a different post, so the two pages wouldn't always point at each other.
 */
final readonly class TagSitemapEntries implements SitemapEntries
{

	public function __construct(
		private Articles $articles,
		private Translator $translator,
	) {
	}


	#[Override]
	public function getEntries(): array
	{
		$locale = $this->translator->getDefaultLocale();
		$entries = [];
		foreach (array_keys($this->articles->getAllTags()) as $tag) {
			$entries[] = new SitemapEntry([$locale => ['tag' => $tag]]);
		}
		return $entries;
	}

}
