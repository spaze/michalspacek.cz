<?php
namespace MichalSpacekCz;

/**
 * Articles model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Articles extends BaseModel
{


	public function getAll($limit = null)
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
			$this->database->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		$articles = $this->database->fetchAll($query);
		foreach ($articles as &$article) {
			$article['excerpt'] = $this->texyFormatter->format($article['excerpt']);
		}
		return $articles;
	}


}