<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Blog;

use Nette\Caching\Cache;
use Nette\Neon\Exception;
use Nette\Utils\Json;

/**
 * Blog post service.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
class Post
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var Post\Loader */
	protected $loader;

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/** @var \Nette\Caching\Cache */
	protected $exportsCache;


	/**
	 * @param \Nette\Database\Context $context
	 * @param Post\Loader $loader
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \Nette\Caching\IStorage $cacheStorage
	 */
	public function __construct(\Nette\Database\Context $context, Post\Loader $loader, \MichalSpacekCz\Formatter\Texy $texyFormatter, \Nette\Caching\IStorage $cacheStorage)
	{
		$this->database = $context;
		$this->loader = $loader;
		$this->texyFormatter = $texyFormatter;
		$this->exportsCache = new Cache($cacheStorage, \MichalSpacekCz\Exports::class);
	}


	/**
	 * Get post.
	 *
	 * @param string $post
	 * @param string $previewKey
	 * @return \MichalSpacekCz\Blog\Post\Data|null
	 */
	public function get(string $post, ?string $previewKey = null): ?\MichalSpacekCz\Blog\Post\Data
	{
		$result = $this->loader->fetch($post, $previewKey);
		return ($result ? $this->format($this->build($result)) : null);
	}


	/**
	 * Get post by id.
	 *
	 * @return MichalSpacekCz\Blog\Post\Data|null
	 */
	public function getById($id): ?\MichalSpacekCz\Blog\Post\Data
	{
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
			WHERE bp.id_blog_post = ?',
			$id
		);
		return ($result ? $this->format($this->build($result)) : null);
	}


	/**
	 * Get all posts.
	 *
	 * @return \MichalSpacekCz\Blog\Post\Data[]
	 */
	public function getAll(): array
	{
		$posts = [];
		$sql = 'SELECT
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
				bp.tags,
				bp.slug_tags AS slugTags
			FROM
				blog_posts bp
			LEFT JOIN blog_post_locales l
				ON l.id_blog_post_locale = bp.key_locale
			ORDER BY
				published, slug';
		foreach ($this->database->fetchAll($sql) as $post) {
			$posts[] = $this->format($this->build($post));
		}
		return $posts;
	}


	/**
	 * Build post data object from database row object.
	 *
	 * @param \Nette\Database\Row $row
	 * @return \MichalSpacekCz\Blog\Post\Data
	 */
	private function build(\Nette\Database\Row $row): \MichalSpacekCz\Blog\Post\Data
	{
		$post = new \MichalSpacekCz\Blog\Post\Data();
		$post->translationGroupId = $row->translationGroupId;
		$post->locale = $row->locale;
		$post->localeId = $row->localeId;
		$post->postId = $row->postId;
		$post->slug = $row->slug;
		$post->titleTexy = $row->titleTexy;
		$post->leadTexy = $row->leadTexy;
		$post->textTexy = $row->textTexy;
		$post->published = $row->published;
		$post->previewKey = $row->previewKey;
		$post->originallyTexy = $row->originallyTexy;
		$post->ogImage = (isset($row->ogImage) ? $row->ogImage : null);  // Can't use ??, throws Nette\MemberAccessException
		$post->tags = (isset($row->tags) ? Json::decode($row->tags) : []);
		$post->slugTags = (isset($row->slugTags) ? Json::decode($row->slugTags) : []);
		$post->recommended = (isset($row->recommended) ? $row->recommended : null);  // Can't use ??, throws Nette\MemberAccessException
		$post->twitterCard = (isset($row->twitterCard) ? $row->twitterCard : null);  // Can't use ??, throws Nette\MemberAccessException
		return $post;
	}


	/**
	 * Format post data.
	 *
	 * @param \MichalSpacekCz\Blog\Post\Data $post
	 * @return \MichalSpacekCz\Blog\Post\Data
	 */
	public function format(\MichalSpacekCz\Blog\Post\Data $post): \MichalSpacekCz\Blog\Post\Data
	{
		$post->recommended = (empty($post->recommended) ? null : Json::decode($post->recommended));
		foreach(['title'] as $item) {
			$post->$item = $this->texyFormatter->format($post->{$item . 'Texy'});
		}
		$this->texyFormatter->setTopHeading(2);
		foreach(['lead', 'text', 'originally'] as $item) {
			$post->$item = $this->texyFormatter->formatBlock($post->{$item . 'Texy'});
		}
		return $post;
	}


	/**
	 * Add a post.
	 *
	 * @param \MichalSpacekCz\Blog\Post\Data $post
	 */
	public function add(\MichalSpacekCz\Blog\Post\Data $post): void
	{
		$this->database->beginTransaction();
		try {
			$this->database->query(
				'INSERT INTO blog_posts',
				array(
					'key_translation_group' => $post->translationGroupId,
					'key_locale' => $post->locale,
					'title' => $post->titleTexy,
					'preview_key' => $post->previewKey,
					'slug' => $post->slug,
					'lead' => $post->leadTexy,
					'text' => $post->textTexy,
					'published' => $post->published,
					'published_timezone' => $post->published->getTimezone()->getName(),
					'originally' => $post->originallyTexy,
					'key_twitter_card_type' => ($post->twitterCard !== null ? $this->getTwitterCardId($post->twitterCard) : null),
					'og_image' => $post->ogImage,
					'tags' => Json::encode($post->tags),
					'slug_tags' => Json::encode($post->slugTags),
					'recommended' => $post->recommended,
				)
			);
			$this->exportsCache->clean([Cache::TAGS => array_merge([self::class], $post->slugTags)]);
			$this->database->commit();
		} catch (Exception $e) {
			$this->database->rollBack();
		}
	}


	/**
	 * Update a post.
	 *
	 * @param \MichalSpacekCz\Blog\Post\Data $post
	 */
	public function update(\MichalSpacekCz\Blog\Post\Data $post): void
	{
		$this->database->beginTransaction();
		try {
			$this->database->query(
				'UPDATE blog_posts SET ? WHERE id_blog_post = ?',
				array(
					'key_translation_group' => $post->translationGroupId,
					'key_locale' => $post->locale,
					'title' => $post->titleTexy,
					'preview_key' => $post->previewKey,
					'slug' => $post->slug,
					'lead' => $post->leadTexy,
					'text' => $post->textTexy,
					'published' => $post->published,
					'published_timezone' => $post->published->getTimezone()->getName(),
					'originally' => $post->originallyTexy,
					'key_twitter_card_type' => ($post->twitterCard !== null ? $this->getTwitterCardId($post->twitterCard) : null),
					'og_image' => $post->ogImage,
					'tags' => ($post->tags ? Json::encode($post->tags) : null),
					'slug_tags' => ($post->slugTags ? Json::encode($post->slugTags) : null),
					'recommended' => $post->recommended,
				),
				$post->postId
			);
			if ($post->editSummary) {
				$now = new \DateTime();
				$this->database->query(
					'INSERT INTO blog_post_edits',
					array(
						'key_blog_post' => $post->postId,
						'edited_at' => $now,
						'edited_at_timezone' => $now->getTimezone()->getName(),
						'summary' => $post->editSummary,
					)
				);
			}
			$cacheTags = [self::class . "/id/{$post->postId}"];
			foreach (array_merge(array_diff($post->slugTags, $post->previousSlugTags), array_diff($post->previousSlugTags, $post->slugTags)) as $tag) {
				$cacheTags[] = self::class . "/tag/{$tag}";
			}
			$this->exportsCache->clean([Cache::TAGS => $cacheTags]);
			$this->database->commit();
		} catch (Exception $e) {
			$this->database->rollBack();
		}
	}


	/**
	 * Get all Twitter card types.
	 *
	 * @return \Nette\Database\Row[]
	 */
	public function getAllTwitterCards(): array
	{
		return $this->database->fetchAll('SELECT id_twitter_card_type AS cardId, card, title FROM twitter_card_types ORDER BY card');
	}


	/**
	 * @param string $card
	 * @return integer
	 */
	private function getTwitterCardId(string $card): int
	{
		return $this->database->fetchField('SELECT id_twitter_card_type FROM twitter_card_types WHERE card = ?', $card);
	}


	/**
	 * Get all blog post locales.
	 *
	 * @return array of id => locale
	 */
	public function getAllLocales(): array
	{
		return $this->database->fetchPairs('SELECT id_blog_post_locale, locale FROM blog_post_locales ORDER BY id_blog_post_locale');
	}


	/**
	 * Get locales and URLs for a blog post.
	 *
	 * @param string $slug
	 * @return \MichalSpacekCz\Blog\Post\Data[]
	 */
	public function getLocaleUrls(string $slug): array
	{
		$posts = [];
		$sql = 'SELECT
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
				bp.tags
			FROM
				blog_posts bp
			LEFT JOIN blog_post_locales l ON l.id_blog_post_locale = bp.key_locale
			WHERE bp.key_translation_group = (SELECT key_translation_group FROM blog_posts WHERE slug = ?)';
		foreach ($this->database->fetchAll($sql, $slug) as $post) {
			$posts[] = $this->format($this->build($post));
		}
		return $posts;
	}


	/**
	 * @param integer $postId
	 * @return Post\Edit[]
	 */
	public function getEdits(int $postId): array
	{
		$sql = 'SELECT
				edited_at AS editedAt,
				edited_at_timezone AS editedAtTimezone,
				summary AS summaryTexy
			FROM blog_post_edits
			WHERE key_blog_post = ?
			ORDER BY edited_at DESC';
		$edits = array();
		foreach ($this->database->fetchAll($sql, $postId) as $row) {
			$edit = new Post\Edit();
			$edit->summaryTexy = $row->summaryTexy;
			$edit->summary = $this->texyFormatter->format($row->summaryTexy);
			$edit->editedAt = $row->editedAt;
			$edit->editedAt->setTimezone(new \DateTimeZone($row->editedAtTimezone));
			$edits[] = $edit;
		}
		return $edits;
	}


	/**
	 * Convert tags string to JSON.
	 *
	 * @param string $tags
	 * @return string[]
	 */
	public function tagsToArray(string $tags): array
	{
		return array_filter(preg_split('/\s*,\s*/', $tags));
	}


	/**
	 * @param string $tags
	 * @return array
	 */
	public function getSlugTags(string $tags): array
	{
		return ($tags ? array_map([\Nette\Utils\Strings::class, 'webalize'], $this->tagsToArray($tags)) : []);
	}

}
