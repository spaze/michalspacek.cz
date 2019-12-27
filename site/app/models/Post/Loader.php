<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Post;

use Contributte\Translation\Translator;
use Nette\Database\Context;
use Nette\Database\Row;
use Nette\Localization\ITranslator;
use Nette\Utils\DateTime;

/**
 * Blog post loader service.
 *
 * Fast loader, no extra work, no formatting, no circular references.
 */
class Loader
{

	/** @var Context */
	protected $database;

	/** @var ITranslator */
	protected $translator;

	/** @var Row|null */
	protected $post;


	/**
	 * @param Context $context
	 * @param Translator|ITranslator $translator
	 */
	public function __construct(Context $context, ITranslator $translator)
	{
		$this->database = $context;
		$this->translator = $translator;
	}


	/**
	 * Check whether the post exists.
	 *
	 * @param string $post
	 * @param string|null $previewKey
	 * @return boolean
	 */
	public function exists(string $post, ?string $previewKey = null): bool
	{
		return (bool)$this->fetch($post, $previewKey);
	}


	/**
	 * Fetch post.
	 *
	 * @param string $post
	 * @param string $previewKey
	 * @return Row|null
	 */
	public function fetch(string $post, ?string $previewKey = null): ?Row
	{
		if ($this->post === null) {
			/** @var Row|null $result */
			$result = $this->database->fetch(
				'SELECT
					bp.id_blog_post AS postId,
					l.id_blog_post_locale AS localeId,
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
					tct.card AS twitterCard
				FROM blog_posts bp
				LEFT JOIN blog_post_locales l
					ON l.id_blog_post_locale = bp.key_locale
				LEFT JOIN twitter_card_types tct
					ON tct.id_twitter_card_type = bp.key_twitter_card_type
				WHERE bp.slug = ?
					AND l.locale = ?
					AND (bp.published <= ? OR bp.preview_key = ?)',
				$post,
				$this->translator->getDefaultLocale(),
				new DateTime(),
				$previewKey
			);
			$this->post = $result;
		}
		return $this->post;
	}

}
