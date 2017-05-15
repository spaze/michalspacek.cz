<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

/**
 * Articles model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Articles
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \Netxten\Formatter\Texy */
	protected $texyFormatter;

	/** @var \Nette\Application\LinkGenerator */
	protected $linkGenerator;

	/** @var \MichalSpacekCz\Blog\Post */
	protected $blogPost;

	/** @var \Nette\Localization\ITranslator */
	protected $translator;


	/**
	 * @param \Nette\Database\Context $context
	 * @param \Netxten\Formatter\Texy $texyFormatter
	 * @param \Nette\Application\LinkGenerator $linkGenerator
	 * @param \MichalSpacekCz\Blog\Post $blogPost
	 * @param \Kdyby\Translation\Translator|\Nette\Localization\ITranslator $translator
	 */
	public function __construct(
		\Nette\Database\Context $context,
		\Netxten\Formatter\Texy $texyFormatter,
		\Nette\Application\LinkGenerator $linkGenerator,
		\MichalSpacekCz\Blog\Post $blogPost,
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
				a.date,
				a.excerpt,
				null AS text,
				s.name AS sourceName,
				s.href AS sourceHref
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
					null
				FROM blog_posts bp
				LEFT JOIN blog_post_locales l
					ON l.id_blog_post_locale = bp.key_locale
				WHERE bp.published <= ?
					AND l.locale = ?
			ORDER BY date DESC';

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
					bp.published AS date,
					bp.lead as excerpt,
					bp.text,
					null AS sourceName,
					null AS sourceHref
				FROM blog_posts bp
				LEFT JOIN blog_post_locales l
					ON l.id_blog_post_locale = bp.key_locale
				WHERE
					JSON_CONTAINS(bp.slug_tags, ?)
					AND bp.published <= ?
					AND l.locale = ?
			ORDER BY date DESC';

		if ($limit !== null) {
			$this->database->getConnection()->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		$articles = $this->database->fetchAll($query, \Nette\Utils\Json::encode($tags), new \Nette\Utils\DateTime(), $this->translator->getDefaultLocale());
		return $this->enrichArticles($articles);
	}


	private function enrichArticles(array $articles): array
	{
		foreach ($articles as $article) {
			$article->updated = null;
			if ($article->sourceHref === null) {
				$edits = $this->blogPost->getEdits($article->articleId);
				$article->updated = ($edits ? current($edits)->editedAt : null);
				$article->href = $this->linkGenerator->link('Blog:Post:', [$article->href]);
				$article->sourceName = $this->translator->translate('messages.title.blog');
				$article->sourceHref = $this->linkGenerator->link('Www:Articles:');
			}
			$article->excerpt = $this->texyFormatter->format($article->excerpt);
			$article->text = $this->texyFormatter->format($article->text);
		}
		return $articles;
	}

}
