<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTimeInterface;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\Sitemap\SitemapEntries;
use MichalSpacekCz\Sitemap\SitemapEntry;
use Nette\Database\Explorer;
use Override;

/**
 * Every published blog post, a post and its published translations as one entry; a translation that isn't
 * published yet has no page to link to.
 */
final readonly class BlogPostSitemapEntries implements SitemapEntries
{

	public function __construct(
		private Explorer $database,
		private DateTimeFactory $dateTimeFactory,
	) {
	}


	#[Override]
	public function getEntries(): array
	{
		$rows = $this->database->fetchAll(
			'SELECT
				bp.slug,
				l.locale,
				bp.key_translation_group AS translationGroupId,
				GREATEST(bp.published, COALESCE((SELECT MAX(e.edited_at) FROM blog_post_edits e WHERE e.key_blog_post = bp.id_blog_post), bp.published)) AS lastModified
			FROM blog_posts bp
				JOIN locales l ON l.id_locale = bp.key_locale
			WHERE bp.published IS NOT NULL AND bp.published <= ?
			ORDER BY bp.published DESC',
			$this->dateTimeFactory->getNow(),
		);
		$params = $lastModified = [];
		foreach ($rows as $row) {
			assert(is_string($row->slug));
			assert(is_string($row->locale));
			assert($row->translationGroupId === null || is_int($row->translationGroupId));
			assert($row->lastModified instanceof DateTimeInterface);
			$key = $row->translationGroupId !== null ? "group/{$row->translationGroupId}" : "post/{$row->locale}/{$row->slug}";
			$params[$key][$row->locale] = ['slug' => $row->slug];
			$lastModified[$key][$row->locale] = $row->lastModified;
		}
		$entries = [];
		foreach ($params as $key => $localeParams) {
			$entries[] = new SitemapEntry($localeParams, $lastModified[$key]);
		}
		return $entries;
	}

}
