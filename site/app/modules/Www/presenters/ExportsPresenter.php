<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use Spaze\Exports\Atom;
use Spaze\Exports\Atom\Constructs;
use Spaze\Exports\Atom\Elements;

/**
 * Exports presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ExportsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Articles */
	protected $articles;


	/**
	 * @param \MichalSpacekCz\Articles $articles
	 */
	public function __construct(\MichalSpacekCz\Articles $articles)
	{
		$this->articles = $articles;
		parent::__construct();
	}


	public function actionArticles(?string $param = null): void
	{
		$self = $this->link('//this');
		$feed = new Atom\Feed($self, 'title');
		$feed->setLinkSelf($self);
		$feed->setAuthor(new Constructs\Person('Michal Špaček'));

		$articles = ($param ? $this->articles->getAllByTags($param, 10) : $this->articles->getAll(10));
		if (!$articles) {
			throw new \Nette\Application\BadRequestException('No articles', \Nette\Http\Response::S404_NOT_FOUND);
		}

		$feedUpdated = null;
		foreach ($articles as $article) {
			$updated = ($article->updated ?? $article->date);
			$entry = new Elements\Entry(
				$article->href,
				new Constructs\Text((string)$article->title, Constructs\Text::TYPE_HTML),
				new Constructs\Text((string)($article->text ?? $article->excerpt), Constructs\Text::TYPE_HTML),
				$updated,
				$article->date
			);
			$entry->addLink(new Elements\Link($article->href, Elements\Link::REL_ALTERNATE, 'text/' . Constructs\Text::TYPE_HTML));
			$feed->addEntry($entry);
			if ($updated > $feedUpdated) {
				$feedUpdated = $updated;
			}
		}
		$feed->setUpdated($feedUpdated);

		$this->sendResponse(new \Spaze\Exports\Bridges\Nette\Atom\Response($feed));
	}

}
