<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Post;

use MichalSpacekCz\Tags\Tags;
use Nette\Database\Explorer;
use Nette\Utils\JsonException;

class LocaleUrls
{

	public function __construct(
		private readonly Explorer $database,
		private readonly Tags $tags,
	) {
	}


	/**
	 * Get locales and URLs for a blog post.
	 *
	 * @param string $slug
	 * @return Data[]
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
			LEFT JOIN blog_post_locales l ON l.id_blog_post_locale = bp.key_locale
			WHERE bp.key_translation_group = (SELECT key_translation_group FROM blog_posts WHERE slug = ?)
				OR bp.slug = ?
			ORDER BY l.id_blog_post_locale';
		foreach ($this->database->fetchAll($sql, $slug, $slug) as $row) {
			$post = new Data();
			$post->locale = $row->locale;
			$post->slug = $row->slug;
			$post->published = $row->published;
			$post->previewKey = $row->previewKey;
			$post->slugTags = ($row->slugTags !== null ? $this->tags->unserialize($row->slugTags) : []);
			$posts[] = $post;
		}
		return $posts;
	}

}
