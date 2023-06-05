<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use Contributte\Translation\Translator;
use DateTime;
use DateTimeZone;
use MichalSpacekCz\Application\LocaleLinkGeneratorInterface;
use MichalSpacekCz\Articles\ArticleEdit;
use MichalSpacekCz\Articles\Blog\Exceptions\BlogPostDoesNotExistException;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Tags\Tags;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Caching\Cache;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Neon\Exception;
use Nette\Utils\Html;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class BlogPosts
{

	/** @var array<int, string>|null */
	private ?array $locales = null;


	/**
	 * @param array<string, array<string, array<int, string>>> $allowedTags
	 */
	public function __construct(
		private readonly Explorer $database,
		private readonly BlogPostLoader $loader,
		private readonly TexyFormatter $texyFormatter,
		private readonly Cache $exportsCache,
		private readonly LinkGenerator $linkGenerator,
		private readonly LocaleLinkGeneratorInterface $localeLinkGenerator,
		private readonly Tags $tags,
		private readonly Translator $translator,
		private readonly int $updatedInfoThreshold,
		private readonly array $allowedTags,
	) {
	}


	public function getUpdatedInfoThreshold(): int
	{
		return $this->updatedInfoThreshold;
	}


	/**
	 * @return array<string, array<string, array<int, string>>>
	 */
	public function getAllowedTags(): array
	{
		return $this->allowedTags;
	}


	/**
	 * @throws InvalidLinkException
	 * @throws JsonException
	 * @throws \MichalSpacekCz\Articles\Blog\Exceptions\BlogPostDoesNotExistException
	 */
	public function get(string $post, ?string $previewKey = null): BlogPost
	{
		$result = $this->loader->fetch($post, $previewKey);
		if (!$result) {
			throw new BlogPostDoesNotExistException(name: $post, previewKey: $previewKey);
		} else {
			return $this->buildPost($result);
		}
	}


	/**
	 * @throws InvalidLinkException
	 * @throws JsonException
	 * @throws BlogPostDoesNotExistException
	 */
	public function getById(int $id): BlogPost
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
		if (!$result) {
			throw new BlogPostDoesNotExistException(id: $id);
		} else {
			return $this->buildPost($result);
		}
	}


	/**
	 * @return list<BlogPost>
	 * @throws InvalidLinkException
	 * @throws JsonException
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
	public function enrich(BlogPost $post): void
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


	public function format(BlogPost $post): BlogPost
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
		$post->title = $this->texyFormatter->format($post->titleTexy, $texy);
		$post->text = $this->texyFormatter->formatBlock($post->textTexy, $texy);
		$post->lead = $post->leadTexy ? $this->texyFormatter->formatBlock($post->leadTexy, $texy) : null;
		$post->originally = $post->originallyTexy ? $this->texyFormatter->formatBlock($post->originallyTexy, $texy) : null;
		return $post;
	}


	/**
	 * @throws JsonException
	 */
	public function add(BlogPost $post): void
	{
		$this->database->beginTransaction();
		try {
			/** @var DateTimeZone|null $timeZone */
			$timeZone = $post->published?->getTimezone() ?: null;
			$this->database->query(
				'INSERT INTO blog_posts',
				[
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
				],
			);
			$post->postId = (int)$this->database->getInsertId();
			$this->exportsCache->clean([Cache::Tags => array_merge([self::class], $post->slugTags)]);
			$this->database->commit();
		} catch (Exception) {
			$this->database->rollBack();
		}
	}


	/**
	 * @throws JsonException
	 */
	public function update(BlogPost $post): void
	{
		$this->database->beginTransaction();
		try {
			$timeZone = $post->published?->getTimezone() ?: null;
			$this->database->query(
				'UPDATE blog_posts SET ? WHERE id_blog_post = ?',
				[
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
				],
				$post->postId,
			);
			$now = new DateTime();
			if ($post->editSummary) {
				/** @var DateTimeZone|false $timeZone */
				$timeZone = $now->getTimezone();
				$this->database->query(
					'INSERT INTO blog_post_edits',
					[
						'key_blog_post' => $post->postId,
						'edited_at' => $now,
						'edited_at_timezone' => ($timeZone ? $timeZone->getName() : date_default_timezone_get()),
						'summary' => $post->editSummary,
					],
				);
			}
			$cacheTags = [self::class . "/id/{$post->postId}"];
			foreach (array_merge(array_diff($post->slugTags, $post->previousSlugTags), array_diff($post->previousSlugTags, $post->slugTags)) as $tag) {
				$cacheTags[] = self::class . "/tag/{$tag}";
			}
			if ($post->needsPreviewKey($now)) {
				$cacheTags[] = self::class;
			}
			$this->exportsCache->clean([Cache::Tags => $cacheTags]);
			$this->database->commit();
		} catch (Exception) {
			$this->database->rollBack();
		}
	}


	/**
	 * @return list<Row>
	 */
	public function getAllTwitterCards(): array
	{
		return array_values($this->database->fetchAll('SELECT id_twitter_card_type AS cardId, card, title FROM twitter_card_types ORDER BY card'));
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
	 * @return list<ArticleEdit>
	 */
	private function getEdits(int $postId): array
	{
		$sql = 'SELECT
				edited_at AS editedAt,
				edited_at_timezone AS editedAtTimezone,
				summary AS summaryTexy
			FROM blog_post_edits
			WHERE key_blog_post = ?
			ORDER BY edited_at DESC';
		$edits = [];
		foreach ($this->database->fetchAll($sql, $postId) as $row) {
			$summary = $this->texyFormatter->format($row->summaryTexy);
			$edit = new ArticleEdit();
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
	public function buildPost(Row $row): BlogPost
	{
		$post = new BlogPost();
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
		$post->edits = $this->getEdits($post->postId);
		$this->enrich($post);
		return $this->format($post);
	}


	public function setTemplateTitleAndHeader(BlogPost $post, DefaultTemplate $template, ?Html $el = null): void
	{
		$title = ($el ?? Html::el())->addHtml($post->title);
		$template->pageTitle = strip_tags((string)$title);
		$template->pageHeader = $title;
	}

}
