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


	public function __construct(\Nette\Database\Context $context, \Netxten\Formatter\Texy $texyFormatter)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
	}


	/**
	 * Get articles sorted by date, newest first.
	 *
	 * @param int|null $limit Null means all, for real
	 * @return array of \Nette\Database\Row
	 */
	public function getAll(int $limit = null): array
	{
		$query = 'SELECT
				a.title,
				a.href,
				a.date,
				a.excerpt,
				s.name AS sourceName,
				s.href AS sourceHref
			FROM articles a
				JOIN article_sources s ON a.key_article_source = s.id_article_source
			ORDER BY date DESC';

		if ($limit !== null) {
			$this->database->getConnection()->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		$articles = $this->database->fetchAll($query);
		foreach ($articles as &$article) {
			$article->excerpt = $this->texyFormatter->format($article->excerpt);
		}
		return $articles;
	}

}
