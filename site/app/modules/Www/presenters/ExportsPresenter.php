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

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;


	/**
	 * @param \MichalSpacekCz\Articles $articles
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 */
	public function __construct(\MichalSpacekCz\Articles $articles, \MichalSpacekCz\Formatter\Texy $texyFormatter)
	{
		$this->articles = $articles;
		$this->texyFormatter = $texyFormatter;
		parent::__construct();
	}


	public function actionArticles(?string $param = null): void
	{
		$self = $this->link('//this');
		$title = ($param ? $this->texyFormatter->translate('messages.feed.articlesbytag', [$param]) : $this->translator->translate('messages.feed.allarticles'));
		$feed = new Atom\Feed($self, "Michal Špaček: {$title}");
		$feed->setLinkSelf($self);
		$feed->setAuthor(new Constructs\Person('Michal Špaček'));

		$articles = ($param ? $this->articles->getAllByTags($param, 10) : $this->articles->getAll(10));
		if (!$articles) {
			throw new \Nette\Application\BadRequestException('No articles', \Nette\Http\Response::S404_NOT_FOUND);
		}

		$feedUpdated = null;
		$template = $this->createTemplate()->setFile(__DIR__ . '/templates/Exports/entry.latte');
		foreach ($articles as $article) {
			$template->article = $article;
			$updated = ($article->updated ?? $article->date);
			$entry = new Elements\Entry(
				$article->href,
				new Constructs\Text((string)$article->title, Constructs\Text::TYPE_HTML),
				new Constructs\Text((string)$template, Constructs\Text::TYPE_HTML),
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

		$this->lastModified($feedUpdated, sha1((string)$feed), 3600);
		$this->sendResponse(new \Spaze\Exports\Bridges\Nette\Atom\Response($feed));
	}

}
