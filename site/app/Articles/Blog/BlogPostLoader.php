<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use Contributte\Translation\Translator;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Utils\DateTime;

/**
 * Blog post loader service.
 *
 * Fast loader, no extra work, no formatting, no circular references.
 */
class BlogPostLoader
{

	private ?Row $post = null;


	public function __construct(
		private readonly Explorer $database,
		private readonly Translator $translator,
	) {
	}


	public function exists(string $post, ?string $previewKey = null): bool
	{
		return (bool)$this->fetch($post, $previewKey);
	}


	public function fetch(string $post, ?string $previewKey = null): ?Row
	{
		if ($this->post === null) {
			$result = $this->database->fetch(
				'SELECT
					bp.id_blog_post AS id,
					l.id_locale AS localeId,
					bp.key_translation_group AS translationGroupId,
					l.locale,
					bp.slug,
					bp.title AS titleTexy,
					bp.lead AS leadTexy,
					bp.text AS textTexy,
					bp.published,
					bp.preview_key AS previewKey,
					bp.originally AS originallyTexy,
					bp.og_image AS ogImage,
					bp.tags,
					bp.slug_tags AS slugTags,
					bp.recommended,
					bp.csp_snippets AS cspSnippets,
					bp.allowed_tags AS allowedTags,
					bp.omit_exports as omitExports,
					tct.id_twitter_card_type AS twitterCardId,
					tct.card AS twitterCard,
					tct.title AS twitterCardTitle
				FROM blog_posts bp
				LEFT JOIN locales l
					ON l.id_locale = bp.key_locale
				LEFT JOIN twitter_card_types tct
					ON tct.id_twitter_card_type = bp.key_twitter_card_type
				WHERE bp.slug = ?
					AND l.locale = ?
					AND (bp.published IS NOT NULL AND bp.published <= ? OR bp.preview_key = ?)',
				$post,
				$this->translator->getDefaultLocale(),
				new DateTime(),
				$previewKey,
			);
			$this->post = $result;
		}
		return $this->post;
	}

}
