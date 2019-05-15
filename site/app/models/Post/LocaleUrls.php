<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Post;

use Nette\Database\Context;
use Nette\Utils\Json;

class LocaleUrls
{

	/** @var Context */
	private $database;


	public function __construct(Context $context)
	{
		$this->database = $context;
	}


	/**
	 * Get locales and URLs for a blog post.
	 *
	 * @param string $slug
	 * @return Data[]
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
			$post->slugTags = ($row->slugTags !== null ? Json::decode($row->slugTags) : []);
			$posts[] = $post;
		}
		return $posts;
	}

}
