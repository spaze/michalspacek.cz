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

	/** @var \Nette\Localization\ITranslator */
	protected $translator;


	/**
	 * @param \Nette\Database\Context $context
	 * @param \Netxten\Formatter\Texy $texyFormatter
	 * @param \Nette\Application\LinkGenerator $linkGenerator
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(\Nette\Database\Context $context, \Netxten\Formatter\Texy $texyFormatter, \Nette\Application\LinkGenerator $linkGenerator, \Nette\Localization\ITranslator $translator)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
		$this->linkGenerator = $linkGenerator;
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
				a.title,
				a.href,
				a.date,
				a.excerpt,
				s.name AS sourceName,
				s.href AS sourceHref
			FROM articles a
				JOIN article_sources s ON a.key_article_source = s.id_article_source
			UNION ALL
				SELECT
					bp.title,
					bp.slug,
					bp.published,
					bp.lead,
					null,
					null
				FROM blog_posts bp
			ORDER BY date DESC';

		if ($limit !== null) {
			$this->database->getConnection()->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		$articles = $this->database->fetchAll($query);
		foreach ($articles as $article) {
			if ($article->sourceHref === null) {
				$article->href = $this->linkGenerator->link('Blog:Post:', [$article->href]);
				$article->sourceName = $this->translator->translate('messages.title.blog');
				$article->sourceHref = $this->linkGenerator->link('Www:Articles:');
			}
			$article->excerpt = $this->texyFormatter->format($article->excerpt);
		}
		return $articles;
	}

}
