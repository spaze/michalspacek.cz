<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

use Collator;
use Nette\Utils\Json;

class Articles
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/** @var \Nette\Application\LinkGenerator */
	protected $linkGenerator;

	/** @var \MichalSpacekCz\Post */
	protected $blogPost;

	/** @var \Nette\Localization\ITranslator */
	protected $translator;


	/**
	 * @param \Nette\Database\Context $context
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \Nette\Application\LinkGenerator $linkGenerator
	 * @param \MichalSpacekCz\Post $blogPost
	 * @param \Contributte\Translation\Translator|\Nette\Localization\ITranslator $translator
	 */
	public function __construct(
		\Nette\Database\Context $context,
		\MichalSpacekCz\Formatter\Texy $texyFormatter,
		\Nette\Application\LinkGenerator $linkGenerator,
		\MichalSpacekCz\Post $blogPost,
		\Nette\Localization\ITranslator $translator
	)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
		$this->linkGenerator = $linkGenerator;
		$this->blogPost = $blogPost;
		$this->translator = $translator;
	}


	/**
	 * Get articles sorted by date, newest first.
	 *
	 * @param int|null $limit Null means all, for real
	 * @return array of \Nette\Database\Row
	 */
	public function getAll(?int $limit = null): array
	{
		$query = 'SELECT
				a.id_article AS articleId,
				a.title,
				a.href,
				a.date AS published,
				a.excerpt,
				null AS text,
				s.name AS sourceName,
				s.href AS sourceHref,
				null AS tags,
				null AS slugTags
			FROM articles a
				JOIN article_sources s ON a.key_article_source = s.id_article_source
			UNION ALL
				SELECT
					bp.id_blog_post,
					bp.title,
					bp.slug,
					bp.published,
					bp.lead,
					bp.text,
					null,
					null,
					bp.tags,
					bp.slug_tags
				FROM blog_posts bp
				LEFT JOIN blog_post_locales l
					ON l.id_blog_post_locale = bp.key_locale
				WHERE bp.published <= ?
					AND l.locale = ?
			ORDER BY published DESC';

		if ($limit !== null) {
			$this->database->getConnection()->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		$articles = $this->database->fetchAll($query, new \Nette\Utils\DateTime(), $this->translator->getDefaultLocale());
		return $this->enrichArticles($articles);
	}


	/**
	 * Get articles filtered by tags, sorted by date, newest first.
	 *
	 * @param string $tags
	 * @param int|null $limit Null means all, for real
	 * @return array of \Nette\Database\Row
	 */
	public function getAllByTags(string $tags, ?int $limit = null): array
	{
		$query = 'SELECT
					bp.id_blog_post AS articleId,
					bp.title,
					bp.slug AS href,
					bp.published,
					bp.lead as excerpt,
					bp.text,
					null AS sourceName,
					null AS sourceHref,
					bp.tags,
					bp.slug_tags AS slugTags
				FROM blog_posts bp
				LEFT JOIN blog_post_locales l
					ON l.id_blog_post_locale = bp.key_locale
				WHERE
					JSON_CONTAINS(bp.slug_tags, ?)
					AND bp.published <= ?
					AND l.locale = ?
			ORDER BY bp.published DESC';

		if ($limit !== null) {
			$this->database->getConnection()->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		$articles = $this->database->fetchAll($query, Json::encode($tags), new \Nette\Utils\DateTime(), $this->translator->getDefaultLocale());
		return $this->enrichArticles($articles);
	}


	/**
	 * Get all tags.
	 *
	 * @return string[]
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
					AND bp.published <= ?
					AND l.locale = ?
			ORDER BY bp.published DESC';

		$result = [];
		$rows = $this->database->fetchAll($query, new \Nette\Utils\DateTime(), $this->translator->getDefaultLocale());
		foreach ($rows as $row) {
			$tags = Json::decode($row->tags);
			$slugTags = Json::decode($row->slugTags);
			foreach ($slugTags as $key => $slugTag) {
				$result[$slugTag] = $tags[$key];
			}
		}
		$collator = new Collator($this->translator->getDefaultLocale());
		$collator->asort($result);
		return $result;
	}


	/**
	 * Get label by tags.
	 *
	 * @param string $tags
	 * @return string|null
	 */
	public function getLabelByTags(string $tags): ?string
	{
		$query = 'SELECT
					bp.tags,
					bp.slug_tags AS slugTags
				FROM blog_posts bp
				LEFT JOIN blog_post_locales l
					ON l.id_blog_post_locale = bp.key_locale
				WHERE
					JSON_CONTAINS(bp.slug_tags, ?)
					AND bp.published <= ?
					AND l.locale = ?
			LIMIT 1';
		$tag = $this->database->fetch($query, Json::encode($tags), new \Nette\Utils\DateTime(), $this->translator->getDefaultLocale());
		if ($tag) {
			$tag->tags = ($tag->tags !== null ? Json::decode($tag->tags) : []);
			$tag->slugTags = ($tag->slugTags !== null ? Json::decode($tag->slugTags) : []);

			foreach ($tag->slugTags as $key => $slug) {
				if ($slug === $tags) {
					return $tag->tags[$key] ?? null;
				}
			}
		}
		return null;
	}


	/**
	 * Get nearest publish date of any article.
	 *
	 * @return \DateTime|null
	 */
	public function getNearestPublishDate(): ?\DateTime
	{
		$query = 'SELECT a.date FROM articles a
			UNION ALL
			SELECT bp.published FROM blog_posts bp
				LEFT JOIN blog_post_locales l ON l.id_blog_post_locale = bp.key_locale
			WHERE bp.published > ? AND l.locale = ?
			ORDER BY date
			LIMIT 1';
		return ($this->database->fetchField($query, new \Nette\Utils\DateTime(), $this->translator->getDefaultLocale()) ?: null);
	}


	/**
	 * Get nearest publish date of an article by a tag.
	 *
	 * @param string $tags
	 * @return \DateTime|null
	 */
	public function getNearestPublishDateByTags(string $tags): ?\DateTime
	{
		$query = 'SELECT bp.published FROM blog_posts bp
				LEFT JOIN blog_post_locales l ON l.id_blog_post_locale = bp.key_locale
			WHERE JSON_CONTAINS(bp.slug_tags, ?)
				AND bp.published > ?
				AND l.locale = ?
			ORDER BY bp.published ASC
			LIMIT 1';
		return ($this->database->fetchField($query, Json::encode($tags), new \Nette\Utils\DateTime(), $this->translator->getDefaultLocale()) ?: null);
	}


	private function enrichArticles(array $articles): array
	{
		foreach ($articles as $article) {
			$article->updated = null;
			$article->edits = null;
			$article->tags = (isset($article->tags) ? Json::decode($article->tags) : []);
			$article->slugTags = (isset($article->slugTags) ? Json::decode($article->slugTags) : []);
			$article->isBlogPost = ($article->sourceHref === null);
			$article->title = $this->texyFormatter->format($article->title);
			if ($article->isBlogPost) {
				$article->edits = $this->blogPost->getEdits($article->articleId);
				$article->updated = ($article->edits ? current($article->edits)->editedAt : null);
				$article->href = $this->linkGenerator->link('Www:Post:', [$article->href]);
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
