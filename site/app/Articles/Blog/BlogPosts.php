<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTime;
use MichalSpacekCz\Articles\Blog\Exceptions\BlogPostDoesNotExistException;
use MichalSpacekCz\Articles\Blog\Exceptions\BlogPostWithoutIdException;
use MichalSpacekCz\DateTime\Exceptions\InvalidTimezoneException;
use MichalSpacekCz\Tags\Tags;
use Nette\Application\UI\InvalidLinkException;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Caching\Cache;
use Nette\Database\Explorer;
use Nette\Neon\Exception;
use Nette\Utils\Html;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

readonly class BlogPosts
{

	public function __construct(
		private Explorer $database,
		private BlogPostLoader $loader,
		private BlogPostFactory $factory,
		private Cache $exportsCache,
		private Tags $tags,
		private int $updatedInfoThreshold,
	) {
	}


	public function getUpdatedInfoThreshold(): int
	{
		return $this->updatedInfoThreshold;
	}


	/**
	 * @throws InvalidLinkException
	 * @throws InvalidTimezoneException
	 * @throws JsonException
	 * @throws BlogPostDoesNotExistException
	 */
	public function get(string $post, ?string $previewKey = null): BlogPost
	{
		$result = $this->loader->fetch($post, $previewKey);
		if (!$result) {
			throw new BlogPostDoesNotExistException(name: $post, previewKey: $previewKey);
		} else {
			return $this->factory->createFromDatabaseRow($result);
		}
	}


	/**
	 * @throws InvalidLinkException
	 * @throws InvalidTimezoneException
	 * @throws JsonException
	 * @throws BlogPostDoesNotExistException
	 */
	public function getById(int $id): BlogPost
	{
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
				bp.omit_exports AS omitExports,
				tct.id_twitter_card_type AS twitterCardId,
				tct.card AS twitterCard,
				tct.title AS twitterCardTitle
			FROM blog_posts bp
			LEFT JOIN locales l
				ON l.id_locale = bp.key_locale
			LEFT JOIN twitter_card_types tct
				ON tct.id_twitter_card_type = bp.key_twitter_card_type
			WHERE bp.id_blog_post = ?',
			$id,
		);
		if (!$result) {
			throw new BlogPostDoesNotExistException(id: $id);
		} else {
			return $this->factory->createFromDatabaseRow($result);
		}
	}


	/**
	 * @return list<BlogPost>
	 * @throws InvalidLinkException
	 * @throws InvalidTimezoneException
	 * @throws JsonException
	 */
	public function getAll(): array
	{
		$posts = [];
		$sql = 'SELECT
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
				null AS recommended,
				null AS cspSnippets,
				null AS allowedTags,
				bp.omit_exports AS omitExports,
				null AS twitterCardId
			FROM
				blog_posts bp
			LEFT JOIN locales l
				ON l.id_locale = bp.key_locale
			ORDER BY
				published, slug';
		foreach ($this->database->fetchAll($sql) as $row) {
			$posts[] = $this->factory->createFromDatabaseRow($row);
		}
		return $posts;
	}


	/**
	 * @throws JsonException
	 */
	public function add(BlogPost $post): void
	{
		$this->database->beginTransaction();
		try {
			$timeZone = $post->getPublishTime()?->getTimezone()->getName();
			$this->database->query(
				'INSERT INTO blog_posts',
				[
					'key_translation_group' => $post->getTranslationGroupId(),
					'key_locale' => $post->getLocaleId(),
					'title' => $post->getTitleTexy(),
					'preview_key' => $post->getPreviewKey(),
					'slug' => $post->getSlug(),
					'lead' => $post->getSummaryTexy(),
					'text' => $post->getTextTexy(),
					'published' => $post->getPublishTime(),
					'published_timezone' => $timeZone,
					'originally' => $post->getOriginallyTexy(),
					'key_twitter_card_type' => $post->getTwitterCard()?->getId(),
					'og_image' => $post->getOgImage(),
					'tags' => $post->getTags() ? $this->tags->serialize($post->getTags()) : null,
					'slug_tags' => $this->tags->serialize($post->getSlugTags()),
					'recommended' => $post->getRecommended() ? Json::encode($post->getRecommended()) : null,
					'csp_snippets' => ($post->getCspSnippets() ? Json::encode($post->getCspSnippets()) : null),
					'allowed_tags' => ($post->getAllowedTagsGroups() ? Json::encode($post->getAllowedTagsGroups()) : null),
					'omit_exports' => $post->omitExports(),
				],
			);
			$post = $post->withId((int)$this->database->getInsertId());
			$this->exportsCache->clean([Cache::Tags => array_merge([self::class], $post->getSlugTags())]);
			$this->database->commit();
		} catch (Exception) {
			$this->database->rollBack();
		}
	}


	/**
	 * @param list<string> $previousSlugTags
	 * @throws JsonException
	 * @throws BlogPostWithoutIdException
	 */
	public function update(BlogPost $post, ?string $editSummary, array $previousSlugTags): void
	{
		$postId = $post->getId();
		if ($postId === null) {
			throw new BlogPostWithoutIdException($post->getSlug());
		}
		$this->database->beginTransaction();
		try {
			$timeZone = $post->getPublishTime()?->getTimezone() ?: null;
			$this->database->query(
				'UPDATE blog_posts SET ? WHERE id_blog_post = ?',
				[
					'key_translation_group' => $post->getTranslationGroupId(),
					'key_locale' => $post->getLocaleId(),
					'title' => $post->getTitleTexy(),
					'preview_key' => $post->getPreviewKey(),
					'slug' => $post->getSlug(),
					'lead' => $post->getSummaryTexy(),
					'text' => $post->getTextTexy(),
					'published' => $post->getPublishTime(),
					'published_timezone' => $post->getPublishTime() ? ($timeZone ? $timeZone->getName() : date_default_timezone_get()) : null,
					'originally' => $post->getOriginallyTexy(),
					'key_twitter_card_type' => $post->getTwitterCard()?->getId(),
					'og_image' => $post->getOgImage(),
					'tags' => ($post->getTags() ? $this->tags->serialize($post->getTags()) : null),
					'slug_tags' => ($post->getSlugTags() ? $this->tags->serialize($post->getSlugTags()) : null),
					'recommended' => ($post->getRecommended() ? Json::encode($post->getRecommended()) : null),
					'csp_snippets' => ($post->getCspSnippets() ? Json::encode($post->getCspSnippets()) : null),
					'allowed_tags' => ($post->getAllowedTagsGroups() ? Json::encode($post->getAllowedTagsGroups()) : null),
					'omit_exports' => $post->omitExports(),
				],
				$postId,
			);
			$editedAt = new DateTime();
			if ($editSummary !== null) {
				$timeZone = $editedAt->getTimezone()->getName();
				$this->database->query(
					'INSERT INTO blog_post_edits',
					[
						'key_blog_post' => $postId,
						'edited_at' => $editedAt,
						'edited_at_timezone' => $timeZone,
						'summary' => $editSummary,
					],
				);
			}
			$cacheTags = [sprintf('%s/id/%s', self::class, $postId)];
			foreach (array_merge(array_diff($post->getSlugTags(), $previousSlugTags), array_diff($previousSlugTags, $post->getSlugTags())) as $tag) {
				$cacheTags[] = self::class . "/tag/{$tag}";
			}
			if ($post->needsPreviewKey()) {
				$cacheTags[] = self::class;
			}
			$this->exportsCache->clean([Cache::Tags => $cacheTags]);
			$this->database->commit();
		} catch (Exception) {
			$this->database->rollBack();
		}
	}


	public function setTemplateTitleAndHeader(BlogPost $post, DefaultTemplate $template, ?Html $el = null): void
	{
		$title = ($el ?? Html::el())->addHtml($post->getTitle());
		$template->pageTitle = strip_tags((string)$title);
		$template->pageHeader = $title;
	}

}
