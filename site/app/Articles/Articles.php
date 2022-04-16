<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles;

use Collator;
use Contributte\Translation\Translator;
use DateTime;
use MichalSpacekCz\Formatter\Texy;
use MichalSpacekCz\Post\Post;
use MichalSpacekCz\Tags\Tags;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Utils\DateTime as NetteDateTime;
use Nette\Utils\JsonException;

class Articles
{

	public function __construct(
		private Explorer $database,
		private Texy $texyFormatter,
		private LinkGenerator $linkGenerator,
		private Post $blogPost,
		private Tags $tags,
		private Translator $translator,
	) {
	}


	/**
	 * Get articles sorted by date, newest first.
	 *
	 * @param int|null $limit Null means all, for real
	 * @return Row[]
	 * @throws InvalidLinkException
	 * @throws JsonException
	 */
	public function getAll(?int $limit = null): array
	{
		$query = 'SELECT
				a.id_article AS articleId,
				a.title,
				NULL AS slug,
				a.href,
				a.date AS published,
				a.excerpt,
				null AS text,
				s.name AS sourceName,
				s.href AS sourceHref,
				null AS tags,
				null AS slugTags,
				null AS omitExports
			FROM articles a
				JOIN article_sources s ON a.key_article_source = s.id_article_source
			UNION ALL
				SELECT
					bp.id_blog_post,
					bp.title,
					bp.slug,
					null,
					bp.published,
					bp.lead,
					bp.text,
					null,
					null,
					bp.tags,
					bp.slug_tags,
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
	 * @param string[] $tags
	 * @param int|null $limit Null means all, for real
	 * @return Row[]
	 * @throws InvalidLinkException
	 * @throws JsonException
	 */
	public function getAllByTags(array $tags, ?int $limit = null): array
	{
		$query = 'SELECT
					bp.id_blog_post AS articleId,
					bp.title,
					bp.slug,
					bp.published,
					bp.lead AS excerpt,
					bp.text,
					null AS sourceName,
					null AS sourceHref,
					bp.tags,
					bp.slug_tags AS slugTags,
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
	 * @param string[] $tags
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
	 * @return Row[]
	 * @throws JsonException
	 * @throws InvalidLinkException
	 */
	private function enrichArticles(array $articles): array
	{
		foreach ($articles as $article) {
			$article->updated = null;
			$article->edits = null;
			$article->tags = (isset($article->tags) ? $this->tags->unserialize($article->tags) : []);
			$article->slugTags = (isset($article->slugTags) ? $this->tags->unserialize($article->slugTags) : []);
			$article->isBlogPost = ($article->sourceHref === null);
			$article->title = $this->texyFormatter->format($article->title);
			if ($article->isBlogPost) {
				$article->edits = $this->blogPost->getEdits($article->articleId);
				$article->updated = ($article->edits ? current($article->edits)->editedAt : null);
				$article->href = $this->linkGenerator->link('Www:Post:', [$article->slug]);
				$article->sourceName = null;
				$article->sourceHref = null;
				$this->texyFormatter->setTopHeading(2);
			}
			$article->excerpt = $this->texyFormatter->formatBlock($article->excerpt);
			$article->text = $this->texyFormatter->formatBlock($article->text);
		}
		return $articles;
	}

}
