<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Post;

/**
 * Blog post loader service.
 *
 * Fast loader, no extra work, no formatting, no circular references.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
class Loader
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \Nette\Localization\ITranslator */
	protected $translator;

	/** @var \Nette\Database\Row */
	protected $post;


	/**
	 * @param \Nette\Database\Context $context
	 * @param \Kdyby\Translation\Translator|\Nette\Localization\ITranslator $translator
	 */
	public function __construct(\Nette\Database\Context $context, \Nette\Localization\ITranslator $translator)
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
	 * @return \Nette\Database\Row|null
	 */
	public function fetch(string $post, ?string $previewKey = null): ?\Nette\Database\Row
	{
		if ($this->post === null) {
			$this->post = $this->database->fetch(
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
				new \Nette\Utils\DateTime(),
				$previewKey
			) ?: null;
		}
		return $this->post;
	}

}
