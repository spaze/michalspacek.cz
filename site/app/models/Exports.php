<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

use Nette\Caching\Cache;
use Nette\Utils\Html;
use Spaze\Exports\Atom;
use Spaze\Exports\Atom\Constructs;
use Spaze\Exports\Atom\Elements;

class Exports
{

	/** @var integer */
	private const ITEMS = 10;

	/** @var \MichalSpacekCz\Articles */
	protected $articles;

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/** @var \Nette\Caching\Cache */
	protected $cache;


	public function __construct(\MichalSpacekCz\Articles $articles, \MichalSpacekCz\Formatter\Texy $texyFormatter, \Nette\Caching\IStorage $cacheStorage)
	{
		$this->articles = $articles;
		$this->texyFormatter = $texyFormatter;
		$this->cache = new Cache($cacheStorage, self::class);
	}


	public function getArticles(string $self, ?string $filter = null): Atom\Feed
	{
		/** @var Atom\Feed $feed */
		$feed = $this->cache->load(($filter ? "Atom/ArticlesByTag/{$filter}" : 'Atom/AllArticles'), function(&$dependencies) use ($self, $filter) {
			$nearest = ($filter ? $this->articles->getNearestPublishDateByTags($filter) : $this->articles->getNearestPublishDate());
			$dependencies[Cache::EXPIRATION] = ($nearest instanceof \DateTime ? $nearest->modify('+1 minute') : null);

			$title = ($filter ? $this->texyFormatter->translate('messages.label.articlesbytag', [$filter]) : $this->texyFormatter->translate('messages.label.allarticles'));
			$feed = new Atom\Feed($self, "Michal Špaček: {$title}");
			$feed->setLinkSelf($self);
			$feed->setAuthor(new Constructs\Person('Michal Špaček'));

			$articles = ($filter ? $this->articles->getAllByTags($filter, self::ITEMS) : $this->articles->getAll(self::ITEMS));
			if (!$articles) {
				throw new \Nette\Application\BadRequestException('No articles', \Nette\Http\IResponse::S404_NOT_FOUND);
			}

			$feedUpdated = null;
			$cacheTags = [];
			foreach ($articles as $article) {
				$updated = ($article->updated ?? $article->published);
				$entry = new Elements\Entry(
					$article->href,
					new Constructs\Text((string)$article->title, Constructs\Text::TYPE_HTML),
					$updated,
					$article->published
				);
				if ($article->excerpt) {
					$entry->setSummary(new Constructs\Text(trim((string)$article->excerpt), Constructs\Text::TYPE_HTML));
				}
				if ($article->text) {
					$content = Html::el();
					if ($article->edits) {
						$content->addHtml(Html::el('h3')->setText($this->texyFormatter->translate('messages.blog.post.edits')));
						$edits = Html::el('ul');
						foreach ($article->edits as $edit) {
							$edits->create('li')
								->addHtml(Html::el('em')
									->addHtml(Html::el('strong')->addText($edit->editedAt->format('j.n.')))
									->addText(' ')
									->addHtml($edit->summary));
						}
						$content->addHtml($edits);
					}
					$content->addHtml($article->text);
					$entry->setContent(new Constructs\Text(trim($content->render()), Constructs\Text::TYPE_HTML));
				}
				$entry->addLink(new Elements\Link($article->href, Elements\Link::REL_ALTERNATE, 'text/' . Constructs\Text::TYPE_HTML));
				$feed->addEntry($entry);
				if ($updated > $feedUpdated) {
					$feedUpdated = $updated;
				}
				$type = ($article->isBlogPost ? \MichalSpacekCz\Post::class : \MichalSpacekCz\Articles::class);
				foreach ($article->slugTags as $slugTag) {
					$cacheTags["{$type}/tag/{$slugTag}"] = "{$type}/tag/{$slugTag}";
				}
				$cacheTags[$type] = $type;
				$cacheTags[] = "{$type}/id/{$article->articleId}";
			}
			$dependencies[Cache::TAGS] = array_values($cacheTags);
			$feed->setUpdated($feedUpdated);
			return $feed;
		});
		return $feed;
	}

}
