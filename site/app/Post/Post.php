<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Post;

use Contributte\Translation\Translator;
use DateTime;
use DateTimeZone;
use MichalSpacekCz\Application\LocaleLinkGenerator;
use MichalSpacekCz\Formatter\TexyFormatter;
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
		private TexyFormatter $texyFormatter,
		private Cache $exportsCache,
		private LinkGenerator $linkGenerator,
		private LocaleLinkGenerator $localeLinkGenerator,
		private Tags $tags,
		private Translator $translator,
	) {
	}


	public function getUpdatedInfoThreshold(): int
	{
		return $this->updatedInfoThreshold;
	}


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
	 * @throws InvalidLinkException
	 * @throws JsonException
	 * @throws Throwable
	 */
	public function get(string $post, ?string $previewKey = null): ?Data
	{
		$result = $this->loader->fetch($post, $previewKey);
		return ($result ? $this->buildPost($result) : null);
	}


	/**
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
				bp.csp_snippets AS cspSnippets,
				bp.allowed_tags AS allowedTags,
				bp.omit_exports AS omitExports,
				tct.card AS twitterCard
			FROM blog_posts bp
			LEFT JOIN blog_post_locales l
				ON l.id_blog_post_locale = bp.key_locale
			LEFT JOIN twitter_card_types tct
				ON tct.id_twitter_card_type = bp.key_twitter_card_type
			WHERE bp.id_blog_post = ?',
			$id,
		);
		return ($result ? $this->buildPost($result) : null);
	}


	/**
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
				bp.og_image AS ogImage,
				bp.tags,
				bp.slug_tags AS slugTags,
				null AS recommended,
				null AS cspSnippets,
				null AS allowedTags,
				bp.omit_exports AS omitExports,
				null AS twitterCard
			FROM
				blog_posts bp
			LEFT JOIN blog_post_locales l
				ON l.id_blog_post_locale = bp.key_locale
			ORDER BY
				published, slug';
		foreach ($this->database->fetchAll($sql) as $row) {
			$posts[] = $this->buildPost($row);
		}
		return $posts;
	}


	/**
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
					'omit_exports' => $post->omitExports,
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
					'omit_exports' => $post->omitExports,
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
	 * @return Row[]
	 */
	public function getAllTwitterCards(): array
	{
		return $this->database->fetchAll('SELECT id_twitter_card_type AS cardId, card, title FROM twitter_card_types ORDER BY card');
	}


	private function getTwitterCardId(string $card): int
	{
		return $this->database->fetchField('SELECT id_twitter_card_type FROM twitter_card_types WHERE card = ?', $card);
	}


	/**
	 * @return array<int, string> of id => locale
	 */
	public function getAllLocales(): array
	{
		if ($this->locales === null) {
			$this->locales = $this->database->fetchPairs('SELECT id_blog_post_locale, locale FROM blog_post_locales ORDER BY id_blog_post_locale');
		}
		return $this->locales;
	}


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


	/**
	 * @throws InvalidLinkException
	 * @throws JsonException
	 */
	private function buildPost(Row $row): Data
	{
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
		$post->ogImage = $row->ogImage;
		$post->tags = ($row->tags !== null ? $this->tags->unserialize($row->tags) : []);
		$post->slugTags = ($row->slugTags !== null ? $this->tags->unserialize($row->slugTags) : []);
		$post->recommended = ($row->recommended !== null ? Json::decode($row->recommended) : []);
		$post->twitterCard = $row->twitterCard;
		$post->cspSnippets = ($row->cspSnippets !== null ? Json::decode($row->cspSnippets) : []);
		$post->allowedTags = ($row->allowedTags !== null ? Json::decode($row->allowedTags) : []);
		$post->omitExports = (bool)$row->omitExports;
		$this->enrich($post);
		return $this->format($post);
	}

}
