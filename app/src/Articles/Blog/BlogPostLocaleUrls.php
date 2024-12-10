<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTime;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Tags\Tags;
use Nette\Utils\JsonException;

readonly class BlogPostLocaleUrls
{

	public function __construct(
		private TypedDatabase $typedDatabase,
		private Tags $tags,
	) {
	}


	/**
	 * Get locales and URLs for a blog post.
	 *
	 * @param string $slug
	 * @return list<BlogPostLocaleUrl>
	 * @throws JsonException
	 */
	public function get(string $slug): array
	{
		$posts = [];
		$sql = 'SELECT
				l.locale,
				bp.slug,
				bp.published,
				bp.preview_key AS previewKey,
				bp.slug_tags AS slugTags
			FROM
				blog_posts bp
			LEFT JOIN locales l ON l.id_locale = bp.key_locale
			WHERE bp.key_translation_group = (SELECT key_translation_group FROM blog_posts WHERE slug = ?)
				OR bp.slug = ?
			ORDER BY l.id_locale';
		foreach ($this->typedDatabase->fetchAll($sql, $slug, $slug) as $row) {
			assert(is_string($row->locale));
			assert(is_string($row->slug));
			assert($row->published instanceof DateTime || $row->published === null);
			assert(is_string($row->previewKey) || $row->previewKey === null);
			assert(is_string($row->slugTags) || $row->slugTags === null);
			$post = new BlogPostLocaleUrl(
				$row->locale,
				$row->slug,
				$row->published,
				$row->previewKey,
				$row->slugTags !== null ? $this->tags->unserialize($row->slugTags) : [],
			);
			$posts[] = $post;
		}
		return $posts;
	}

}
