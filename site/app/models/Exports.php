<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

use DateTime;
use MichalSpacekCz\Formatter\Texy;
use Nette\Application\BadRequestException;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Utils\Html;
use Spaze\Exports\Atom\Constructs\Person;
use Spaze\Exports\Atom\Constructs\Text;
use Spaze\Exports\Atom\Elements\Entry;
use Spaze\Exports\Atom\Elements\Link;
use Spaze\Exports\Atom\Feed;

class Exports
{

	/** @var integer */
	private const ITEMS = 5;

	/** @var Articles */
	protected $articles;

	/** @var Texy */
	protected $texyFormatter;

	/** @var Cache */
	protected $cache;


	public function __construct(Articles $articles, Texy $texyFormatter, IStorage $cacheStorage)
	{
		$this->articles = $articles;
		$this->texyFormatter = $texyFormatter;
		$this->cache = new Cache($cacheStorage, self::class);
	}


	public function getArticles(string $self, ?string $filter = null): Feed
	{
		/** @var Feed $feed */
		$feed = $this->cache->load(($filter ? "Atom/ArticlesByTag/{$filter}" : 'Atom/AllArticles'), function(&$dependencies) use ($self, $filter) {
			$nearest = ($filter ? $this->articles->getNearestPublishDateByTags($filter) : $this->articles->getNearestPublishDate());
			$dependencies[Cache::EXPIRATION] = ($nearest instanceof DateTime ? $nearest->modify('+1 minute') : null);

			$title = ($filter ? $this->texyFormatter->translate('messages.label.articlesbytag', [$filter]) : $this->texyFormatter->translate('messages.label.allarticles'));
			$feed = new Feed($self, "Michal Špaček: {$title}");
			$feed->setLinkSelf($self);
			$feed->setAuthor(new Person('Michal Špaček'));

			$articles = ($filter ? $this->articles->getAllByTags($filter, self::ITEMS) : $this->articles->getAll(self::ITEMS));
			if (!$articles) {
				throw new BadRequestException('No articles');
			}

			$feedUpdated = null;
			$cacheTags = [];
			foreach ($articles as $article) {
				$updated = ($article->updated ?? $article->published);
				$entry = new Entry(
					$article->href,
					new Text((string)$article->title, Text::TYPE_HTML),
					$updated,
					$article->published
				);
				if ($article->excerpt) {
					$entry->setSummary(new Text(trim((string)$article->excerpt), Text::TYPE_HTML));
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
					$entry->setContent(new Text(trim($content->render()), Text::TYPE_HTML));
				}
				$entry->addLink(new Link($article->href, Link::REL_ALTERNATE, 'text/' . Text::TYPE_HTML));
				$feed->addEntry($entry);
				if ($updated > $feedUpdated) {
					$feedUpdated = $updated;
				}
				$type = ($article->isBlogPost ? Post::class : Articles::class);
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
