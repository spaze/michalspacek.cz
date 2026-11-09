<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTime;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\Tags\Tags;
use Nette\Database\Explorer;
use Nette\Utils\JsonException;

final readonly class BlogPostLocaleUrls
{

	public function __construct(
		private Explorer $database,
		private Tags $tags,
		private DateTimeFactory $dateTimeFactory,
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
		$now = $this->dateTimeFactory->getNow();
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
		foreach ($this->database->fetchAll($sql, $slug, $slug) as $row) {
			assert(is_string($row->locale));
			assert(is_string($row->slug));
			assert($row->published instanceof DateTime || $row->published === null);
			assert(is_string($row->previewKey) || $row->previewKey === null);
			assert(is_string($row->slugTags) || $row->slugTags === null);
			$post = new BlogPostLocaleUrl(
				$row->locale,
				$row->slug,
				$row->published,
				$row->published !== null && $row->published <= $now,
				$row->previewKey,
				$row->slugTags !== null ? $this->tags->unserialize($row->slugTags) : [],
			);
			$posts[] = $post;
		}
		return $posts;
	}


	/**
	 * The locale link parameters of the post and its published translations. A translation that isn't published could only
	 * be linked with its preview key, and a link with a preview key is for whoever it's sent to, nothing on the site
	 * should lead to one. The post itself is in even when it's a preview, it's what tells the translations apart.
	 *
	 * @return array<string, array{slug: string}> locale => params
	 */
	public function getLinkParams(BlogPost $post): array
	{
		$params = [];
		foreach ($this->get($post->getSlug()) as $localePost) {
			$isThePost = $localePost->getLocale() === $post->getLocale() && $localePost->getSlug() === $post->getSlug();
			if ($isThePost || $localePost->isPublished()) {
				$params[$localePost->getLocale()] = ['slug' => $localePost->getSlug()];
			}
		}
		return $params;
	}

}
