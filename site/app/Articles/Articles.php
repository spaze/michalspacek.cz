<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles;

use Collator;
use Contributte\Translation\Translator;
use DateTime;
use MichalSpacekCz\Articles\Blog\BlogPost;
use MichalSpacekCz\Articles\Blog\BlogPosts;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Tags\Tags;
use Nette\Application\UI\InvalidLinkException;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Utils\DateTime as NetteDateTime;
use Nette\Utils\JsonException;

class Articles
{

	public function __construct(
		private readonly Explorer $database,
		private readonly TexyFormatter $texyFormatter,
		private readonly BlogPosts $blogPosts,
		private readonly Tags $tags,
		private readonly Translator $translator,
	) {
	}


	/**
	 * Get articles sorted by date, newest first.
	 *
	 * @param int|null $limit Null means all, for real
	 * @return list<Article|BlogPost>
	 * @throws InvalidLinkException
	 * @throws JsonException
	 */
	public function getAll(?int $limit = null): array
	{
		$query = 'SELECT
				a.id_article AS id,
				null AS localeId,
				null AS translationGroupId,
				null AS locale,
				a.title AS titleTexy,
				NULL AS slug,
				a.href,
				a.date AS published,
				a.excerpt AS excerptTexy,
				null AS textTexy,
				s.name AS sourceName,
				s.href AS sourceHref,
				null AS previewKey,
				null AS originallyTexy,
				null AS ogImage,
				null AS tags,
				null AS slugTags,
				null AS recommended,
				null AS cspSnippets,
				null AS allowedTags,
				null AS twitterCard,
				null AS omitExports
			FROM articles a
				JOIN article_sources s ON a.key_article_source = s.id_article_source
			UNION ALL
				SELECT
					bp.id_blog_post,
					l.id_blog_post_locale,
					bp.key_translation_group,
					l.locale,
					bp.title,
					bp.slug,
					null,
					bp.published,
					bp.lead,
					bp.text,
					null,
					null,
					bp.preview_key,
					bp.originally,
					bp.og_image AS ogImage,
					bp.tags,
					bp.slug_tags,
					null AS recommended,
					null AS cspSnippets,
					null AS allowedTags,
					null AS twitterCard,
					bp.omit_exports AS omitExports
				FROM blog_posts bp
				LEFT JOIN blog_post_locales l
					ON l.id_blog_post_locale = bp.key_locale
				WHERE bp.published IS NOT NULL
					AND bp.published <= ?
					AND l.locale = ?
			ORDER BY published DESC
			LIMIT ?';

		$articles = $this->database->fetchAll($query, new NetteDateTime(), $this->translator->getDefaultLocale(), $limit ?? PHP_INT_MAX);
		return $this->enrichArticles($articles);
	}


	/**
	 * Get articles filtered by tags, sorted by date, newest first.
	 *
	 * @param list<string> $tags
	 * @param int|null $limit Null means all, for real
	 * @return list<Article|BlogPost>
	 * @throws InvalidLinkException
	 * @throws JsonException
	 */
	public function getAllByTags(array $tags, ?int $limit = null): array
	{
		$query = 'SELECT
					bp.id_blog_post AS id,
					l.id_blog_post_locale AS localeId,
					bp.key_translation_group AS translationGroupId,
					l.locale,
					bp.title AS titleTexy,
					bp.slug,
					bp.published,
					bp.lead AS excerptTexy,
					bp.text AS textTexy,
					null AS sourceName,
					null AS sourceHref,
					bp.preview_key AS previewKey,
					bp.originally AS originallyTexy,
					bp.og_image AS ogImage,
					bp.tags,
					bp.slug_tags AS slugTags,
					null AS recommended,
					null AS cspSnippets,
					null AS allowedTags,
					null AS twitterCard,
					bp.omit_exports AS omitExports
				FROM blog_posts bp
				LEFT JOIN blog_post_locales l
					ON l.id_blog_post_locale = bp.key_locale
				WHERE
					JSON_CONTAINS(bp.slug_tags, ?)
					AND bp.published IS NOT NULL
					AND bp.published <= ?
					AND l.locale = ?
			ORDER BY bp.published DESC
			LIMIT ?';

		$articles = $this->database->fetchAll($query, $this->tags->serialize($tags), new NetteDateTime(), $this->translator->getDefaultLocale(), $limit ?? PHP_INT_MAX);
		return $this->enrichArticles($articles);
	}


	/**
	 * @return string[]
	 * @throws JsonException
	 */
	public function getAllTags(): array
	{
		$query = 'SELECT DISTINCT
					bp.tags,
					bp.slug_tags AS slugTags,
					bp.published
				FROM blog_posts bp
				LEFT JOIN blog_post_locales l
					ON l.id_blog_post_locale = bp.key_locale
				WHERE
					bp.tags IS NOT NULL
					AND bp.published IS NOT NULL
					AND bp.published <= ?
					AND l.locale = ?
			ORDER BY bp.published DESC';

		$result = [];
		$rows = $this->database->fetchAll($query, new NetteDateTime(), $this->translator->getDefaultLocale());
		foreach ($rows as $row) {
			$tags = $this->tags->unserialize($row->tags);
			$slugTags = $this->tags->unserialize($row->slugTags);
			foreach ($slugTags as $key => $slugTag) {
				$result[$slugTag] = $tags[$key];
			}
		}
		$collator = new Collator($this->translator->getDefaultLocale());
		$collator->asort($result);
		return $result;
	}


	/**
	 * @param string $tag
	 * @return string|null
	 * @throws JsonException
	 */
	public function getLabelByTag(string $tag): ?string
	{
		$query = 'SELECT
					bp.tags,
					bp.slug_tags AS slugTags
				FROM blog_posts bp
				LEFT JOIN blog_post_locales l
					ON l.id_blog_post_locale = bp.key_locale
				WHERE
					JSON_CONTAINS(bp.slug_tags, ?)
					AND bp.published IS NOT NULL
					AND bp.published <= ?
					AND l.locale = ?
			LIMIT 1';
		$result = $this->database->fetch($query, $this->tags->serialize([$tag]), new NetteDateTime(), $this->translator->getDefaultLocale());
		if ($result) {
			$result->tags = ($result->tags !== null ? $this->tags->unserialize($result->tags) : []);
			$result->slugTags = ($result->slugTags !== null ? $this->tags->unserialize($result->slugTags) : []);

			foreach ($result->slugTags as $key => $slug) {
				if ($slug === $tag) {
					return $result->tags[$key] ?? null;
				}
			}
		}
		return null;
	}


	/**
	 * Get nearest publish date of any article.
	 *
	 * @return DateTime|null
	 */
	public function getNearestPublishDate(): ?DateTime
	{
		$query = 'SELECT a.date FROM articles a WHERE a.date > ?
			UNION ALL
			SELECT bp.published FROM blog_posts bp
				LEFT JOIN blog_post_locales l ON l.id_blog_post_locale = bp.key_locale
			WHERE bp.published IS NOT NULL AND bp.published > ? AND l.locale = ?
			ORDER BY date
			LIMIT 1';
		$now = new DateTime();
		return ($this->database->fetchField($query, $now, $now, $this->translator->getDefaultLocale()) ?: null);
	}


	/**
	 * @param list<string> $tags
	 * @return DateTime|null
	 * @throws JsonException
	 */
	public function getNearestPublishDateByTags(array $tags): ?DateTime
	{
		$query = 'SELECT bp.published FROM blog_posts bp
				LEFT JOIN blog_post_locales l ON l.id_blog_post_locale = bp.key_locale
			WHERE JSON_CONTAINS(bp.slug_tags, ?)
				AND bp.published IS NOT NULL
				AND bp.published > ?
				AND l.locale = ?
			ORDER BY bp.published
			LIMIT 1';
		return ($this->database->fetchField($query, $this->tags->serialize($tags), new NetteDateTime(), $this->translator->getDefaultLocale()) ?: null);
	}


	/**
	 * @param Row[] $articles
	 * @return list<Article|BlogPost>
	 * @throws JsonException
	 * @throws InvalidLinkException
	 */
	private function enrichArticles(array $articles): array
	{
		$result = [];
		foreach ($articles as $article) {
			if ($article->sourceHref === null) {
				$article->postId = $article->id;
				$article->leadTexy = $article->excerptTexy;
				$result[] = $this->blogPosts->buildPost($article);
			} else {
				$article->articleId = $article->id;
				$result[] = $this->buildArticle($article);
			}
		}
		return $result;
	}


	public function buildArticle(Row $row): Article
	{
		$article = new Article();
		$article->articleId = $row->articleId;
		$article->titleTexy = $row->titleTexy;
		$article->href = $row->href;
		$article->published = $row->published;
		$article->excerptTexy = $row->excerptTexy;
		$article->sourceName = $row->sourceName;
		$article->sourceHref = $row->sourceHref;

		$texy = $this->texyFormatter->getTexy();
		$this->texyFormatter->setTopHeading(2);
		$article->title = $this->texyFormatter->format($article->titleTexy, $texy);
		$article->excerpt = $this->texyFormatter->format($article->excerptTexy, $texy);
		return $article;
	}

}
