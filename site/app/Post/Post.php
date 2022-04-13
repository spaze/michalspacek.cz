<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Post;

use Contributte\Translation\Translator;
use DateTime;
use DateTimeZone;
use MichalSpacekCz\Application\LocaleLinkGenerator;
use MichalSpacekCz\Formatter\Texy;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Tags\Tags;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use Nette\Caching\Cache;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Neon\Exception;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Throwable;

class Post
{

	/** @var string[]|null */
	private ?array $locales = null;

	private int $updatedInfoThreshold;

	/** @var array<string, array<string, array<int, string>>> */
	private array $allowedTags;


	public function __construct(
		private Explorer $database,
		private Loader $loader,
		private Texy $texyFormatter,
		private Cache $exportsCache,
		private LinkGenerator $linkGenerator,
		private LocaleLinkGenerator $localeLinkGenerator,
		private Tags $tags,
		private Translator $translator,
	) {
	}


	/**
	 * @return int
	 */
	public function getUpdatedInfoThreshold(): int
	{
		return $this->updatedInfoThreshold;
	}


	/**
	 * @param int $updatedInfoThreshold
	 */
	public function setUpdatedInfoThreshold(int $updatedInfoThreshold): void
	{
		$this->updatedInfoThreshold = $updatedInfoThreshold;
	}


	/**
	 * @return array<string, array<string, array<int, string>>>
	 */
	public function getAllowedTags(): array
	{
		return $this->allowedTags;
	}


	/**
	 * @param array<string, array<string, array<int, string>>> $allowedTags
	 */
	public function setAllowedTags(array $allowedTags): void
	{
		$this->allowedTags = $allowedTags;
	}


	/**
	 * Get post.
	 *
	 * @param string $post
	 * @param string $previewKey
	 * @return Data|null
	 * @throws InvalidLinkException
	 * @throws JsonException
	 * @throws Throwable
	 */
	public function get(string $post, ?string $previewKey = null): ?Data
	{
		$result = $this->loader->fetch($post, $previewKey);
		$post = new Data();
		$post->postId = $result->postId;
		$post->localeId = $result->localeId;
		$post->translationGroupId = $result->translationGroupId;
		$post->locale = $result->locale;
		$post->slug = $result->slug;
		$post->titleTexy = $result->titleTexy;
		$post->leadTexy = $result->leadTexy;
		$post->textTexy = $result->textTexy;
		$post->published = $result->published;
		$post->previewKey = $result->previewKey;
		$post->originallyTexy = $result->originallyTexy;
		$post->ogImage = $result->ogImage;
		$post->tags = ($result->tags !== null ? $this->tags->unserialize($result->tags) : []);
		$post->slugTags = ($result->slugTags !== null ? $this->tags->unserialize($result->slugTags) : []);
		$post->recommended = ($result->recommended !== null ? Json::decode($result->recommended) : []);
		$post->twitterCard = $result->twitterCard;
		$post->cspSnippets = ($result->cspSnippets !== null ? Json::decode($result->cspSnippets) : []);
		$post->allowedTags = ($result->allowedTags !== null ? Json::decode($result->allowedTags) : []);
		$this->enrich($post);

		return ($result ? $this->format($post) : null);
	}


	/**
	 * Get post by id.
	 *
	 * @param int $id
	 * @return Data|null
	 * @throws InvalidLinkException
	 * @throws JsonException
	 * @throws Throwable
	 */
	public function getById(int $id): ?Data
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
				bp.csp_snippets as cspSnippets,
				bp.allowed_tags as allowedTags,
				tct.card AS twitterCard
			FROM blog_posts bp
			LEFT JOIN blog_post_locales l
				ON l.id_blog_post_locale = bp.key_locale
			LEFT JOIN twitter_card_types tct
				ON tct.id_twitter_card_type = bp.key_twitter_card_type
			WHERE bp.id_blog_post = ?',
			$id,
		);
		$post = new Data();
		$post->postId = $result->postId;
		$post->translationGroupId = $result->translationGroupId;
		$post->locale = $result->locale;
		$post->localeId = $result->localeId;
		$post->slug = $result->slug;
		$post->titleTexy = $result->titleTexy;
		$post->leadTexy = $result->leadTexy;
		$post->textTexy = $result->textTexy;
		$post->originallyTexy = $result->originallyTexy;
		$post->published = $result->published;
		$post->previewKey = $result->previewKey;
		$post->ogImage = $result->ogImage;
		$post->tags = ($result->tags !== null ? $this->tags->unserialize($result->tags) : []);
		$post->slugTags = ($result->slugTags !== null ? $this->tags->unserialize($result->slugTags) : []);
		$post->recommended = ($result->recommended !== null ? Json::decode($result->recommended) : []);
		$post->cspSnippets = ($result->cspSnippets !== null ? Json::decode($result->cspSnippets) : []);
		$post->allowedTags = ($result->allowedTags !== null ? Json::decode($result->allowedTags) : []);
		$post->twitterCard = $result->twitterCard;
		$this->enrich($post);
		return ($result ? $this->format($post) : null);
	}


	/**
	 * Get all posts.
	 *
	 * @return Data[]
	 * @throws InvalidLinkException
	 * @throws JsonException
	 * @throws Throwable
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
		foreach ($this->database->fetchAll($sql) as $row) {
			$post = new Data();
			$post->postId = $row->postId;
			$post->translationGroupId = $row->translationGroupId;
			$post->locale = $row->locale;
			$post->localeId = $row->localeId;
			$post->slug = $row->slug;
			$post->titleTexy = $row->titleTexy;
			$post->leadTexy = $row->leadTexy;
			$post->textTexy = $row->textTexy;
			$post->originallyTexy = $row->originallyTexy;
			$post->published = $row->published;
			$post->previewKey = $row->previewKey;
			$post->tags = ($row->tags !== null ? $this->tags->unserialize($row->tags) : []);
			$post->slugTags = ($row->slugTags !== null ? $this->tags->unserialize($row->slugTags) : []);
			$this->enrich($post);
			$posts[] = $this->format($post);
		}
		return $posts;
	}


	/**
	 * Enrich post data object.
	 *
	 * @param Data $post
	 * @throws InvalidLinkException
	 */
	public function enrich(Data $post): void
	{
		$params = [
			'slug' => $post->slug,
			'preview' => ($post->needsPreviewKey() ? $post->previewKey : null),
		];
		if (!isset($post->locale) || $post->locale === $this->translator->getDefaultLocale()) {
			$post->href = $this->linkGenerator->link('Www:Post:', $params);
		} else {
			$links = $this->localeLinkGenerator->links('Www:Post:', $this->localeLinkGenerator->defaultParams($params));
			$post->href = $links[$post->locale];
		}
	}


	/**
	 * Format post data.
	 *
	 * @param Data $post
	 * @return Data
	 * @throws Throwable
	 */
	public function format(Data $post): Data
	{
		$texy = $this->texyFormatter->getTexy();
		if ($post->allowedTags) {
			$allowedTags = [];
			foreach ($post->allowedTags as $tags) {
				$allowedTags = array_merge($allowedTags, $this->allowedTags[$tags]);
			}
			$texy->allowedTags = $allowedTags;
		}
		$this->texyFormatter->setTopHeading(2);
		$title = $this->texyFormatter->format($post->titleTexy, $texy);
		$text = $this->texyFormatter->formatBlock($post->textTexy, $texy);
		if (!isset($title, $text)) {
			throw new ShouldNotHappenException();
		}
		$post->title = $title;
		$post->text = $text;
		$post->lead = $this->texyFormatter->formatBlock($post->leadTexy, $texy);
		$post->originally = $this->texyFormatter->formatBlock($post->originallyTexy, $texy);
		return $post;
	}


	/**
	 * Add a post.
	 *
	 * @param Data $post
	 * @throws JsonException
	 */
	public function add(Data $post): void
	{
		$this->database->beginTransaction();
		try {
			/** @var DateTimeZone|null $timeZone */
			$timeZone = $post->published?->getTimezone() ?: null;
			$this->database->query(
				'INSERT INTO blog_posts',
				array(
					'key_translation_group' => $post->translationGroupId,
					'key_locale' => $post->localeId,
					'title' => $post->titleTexy,
					'preview_key' => $post->previewKey,
					'slug' => $post->slug,
					'lead' => $post->leadTexy,
					'text' => $post->textTexy,
					'published' => $post->published,
					'published_timezone' => $post->published ? ($timeZone ? $timeZone->getName() : date_default_timezone_get()) : null,
					'originally' => $post->originallyTexy,
					'key_twitter_card_type' => ($post->twitterCard !== null ? $this->getTwitterCardId($post->twitterCard) : null),
					'og_image' => $post->ogImage,
					'tags' => $post->tags ? $this->tags->serialize($post->tags) : null,
					'slug_tags' => $this->tags->serialize($post->slugTags),
					'recommended' => $post->recommended ? Json::encode($post->recommended) : null,
					'csp_snippets' => ($post->cspSnippets ? Json::encode($post->cspSnippets) : null),
					'allowed_tags' => ($post->allowedTags ? Json::encode($post->allowedTags) : null),
				),
			);
			$post->postId = (int)$this->database->getInsertId();
			$this->exportsCache->clean([Cache::TAGS => array_merge([self::class], $post->slugTags)]);
			$this->database->commit();
		} catch (Exception) {
			$this->database->rollBack();
		}
	}


	/**
	 * Update a post.
	 *
	 * @param Data $post
	 * @throws JsonException
	 */
	public function update(Data $post): void
	{
		$this->database->beginTransaction();
		try {
			/** @var DateTimeZone|false $timeZone */
			$timeZone = $post->published?->getTimezone() ?: null;
			$this->database->query(
				'UPDATE blog_posts SET ? WHERE id_blog_post = ?',
				array(
					'key_translation_group' => $post->translationGroupId,
					'key_locale' => $post->localeId,
					'title' => $post->titleTexy,
					'preview_key' => $post->previewKey,
					'slug' => $post->slug,
					'lead' => $post->leadTexy,
					'text' => $post->textTexy,
					'published' => $post->published,
					'published_timezone' => $post->published ? ($timeZone ? $timeZone->getName() : date_default_timezone_get()) : null,
					'originally' => $post->originallyTexy,
					'key_twitter_card_type' => ($post->twitterCard !== null ? $this->getTwitterCardId($post->twitterCard) : null),
					'og_image' => $post->ogImage,
					'tags' => ($post->tags ? $this->tags->serialize($post->tags) : null),
					'slug_tags' => ($post->slugTags ? $this->tags->serialize($post->slugTags) : null),
					'recommended' => ($post->recommended ? Json::encode($post->recommended) : null),
					'csp_snippets' => ($post->cspSnippets ? Json::encode($post->cspSnippets) : null),
					'allowed_tags' => ($post->allowedTags ? Json::encode($post->allowedTags) : null),
				),
				$post->postId,
			);
			$now = new DateTime();
			if ($post->editSummary) {
				/** @var DateTimeZone|false $timeZone */
				$timeZone = $now->getTimezone();
				$this->database->query(
					'INSERT INTO blog_post_edits',
					array(
						'key_blog_post' => $post->postId,
						'edited_at' => $now,
						'edited_at_timezone' => ($timeZone ? $timeZone->getName() : date_default_timezone_get()),
						'summary' => $post->editSummary,
					),
				);
			}
			$cacheTags = [self::class . "/id/{$post->postId}"];
			foreach (array_merge(array_diff($post->slugTags, $post->previousSlugTags), array_diff($post->previousSlugTags, $post->slugTags)) as $tag) {
				$cacheTags[] = self::class . "/tag/{$tag}";
			}
			if ($post->needsPreviewKey($now)) {
				$cacheTags[] = self::class;
			}
			$this->exportsCache->clean([Cache::TAGS => $cacheTags]);
			$this->database->commit();
		} catch (Exception) {
			$this->database->rollBack();
		}
	}


	/**
	 * Get all Twitter card types.
	 *
	 * @return Row[]
	 */
	public function getAllTwitterCards(): array
	{
		return $this->database->fetchAll('SELECT id_twitter_card_type AS cardId, card, title FROM twitter_card_types ORDER BY card');
	}


	/**
	 * @param string $card
	 * @return int
	 */
	private function getTwitterCardId(string $card): int
	{
		return $this->database->fetchField('SELECT id_twitter_card_type FROM twitter_card_types WHERE card = ?', $card);
	}


	/**
	 * Get all blog post locales.
	 *
	 * @return array<int, string> of id => locale
	 */
	public function getAllLocales(): array
	{
		if ($this->locales === null) {
			$this->locales = $this->database->fetchPairs('SELECT id_blog_post_locale, locale FROM blog_post_locales ORDER BY id_blog_post_locale');
		}
		return $this->locales;
	}


	/**
	 * Get locale by its id.
	 *
	 * @param int $id
	 * @return string|null
	 */
	public function getLocaleById(int $id): ?string
	{
		return $this->getAllLocales()[$id] ?? null;
	}


	/**
	 * @param int $postId
	 * @return Edit[]
	 * @throws Throwable
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
			$summary = $this->texyFormatter->format($row->summaryTexy);
			if ($summary === null) {
				throw new ShouldNotHappenException();
			}
			$edit = new Edit();
			$edit->summaryTexy = $row->summaryTexy;
			$edit->summary = $summary;
			$edit->editedAt = $row->editedAt;
			$edit->editedAt->setTimezone(new DateTimeZone($row->editedAtTimezone));
			$edits[] = $edit;
		}
		return $edits;
	}

}
