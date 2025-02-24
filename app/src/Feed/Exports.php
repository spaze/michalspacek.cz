<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Feed;

use Contributte\Translation\Translator;
use DateTime;
use MichalSpacekCz\Articles\Articles;
use MichalSpacekCz\Articles\Components\ArticleWithEdits;
use MichalSpacekCz\Articles\Components\ArticleWithTags;
use MichalSpacekCz\Articles\Components\ArticleWithText;
use MichalSpacekCz\Articles\Components\ArticleWithUpdateTime;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\BadRequestException;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\Html;
use Spaze\Exports\Atom\Constructs\Person;
use Spaze\Exports\Atom\Constructs\Text;
use Spaze\Exports\Atom\Elements\Entry;
use Spaze\Exports\Atom\Elements\Link;
use Spaze\Exports\Atom\Feed;

final readonly class Exports
{

	private const int ITEMS = 5;

	private Cache $cache;


	public function __construct(
		private Articles $articles,
		private TexyFormatter $texyFormatter,
		private Translator $translator,
		Storage $cacheStorage,
	) {
		$this->cache = new Cache($cacheStorage, self::class);
	}


	public function getArticles(string $self, ?string $filter = null): Feed
	{
		$key = sprintf('Atom/%s/%s', $this->translator->getDefaultLocale(), $filter !== null ? "ArticlesByTag/{$filter}" : 'AllArticles');
		$feed = $this->cache->load($key, function (array|null &$dependencies) use ($self, $filter): Feed {
			$nearest = $filter !== null ? $this->articles->getNearestPublishDateByTags([$filter]) : $this->articles->getNearestPublishDate();
			$dependencies[Cache::Expire] = ($nearest instanceof DateTime ? $nearest->modify('+1 minute') : null);

			$title = $filter !== null ? $this->texyFormatter->translate('messages.label.articlesbytag', [$filter]) : $this->texyFormatter->translate('messages.label.allarticles');
			$feed = new Feed($self, "Michal Špaček: {$title}");
			$feed->setLinkSelf($self);
			$feed->setAuthor(new Person('Michal Špaček'));

			$articles = $filter !== null ? $this->articles->getAllByTags([$filter], self::ITEMS) : $this->articles->getAll(self::ITEMS);
			if (!$articles) {
				throw new BadRequestException('No articles');
			}

			$feedUpdated = null;
			$cacheTags = [];
			foreach ($articles as $article) {
				if ($article instanceof ExportsOmittable && $article->omitExports()) {
					continue;
				}
				$publishTime = $article->getPublishTime();
				if ($publishTime === null) {
					throw new ShouldNotHappenException('The $articles array items should all be published already');
				}
				$updated = $publishTime;
				if ($article instanceof ArticleWithUpdateTime) {
					$updateTime = $article->getUpdateTime();
					if ($updateTime !== null) {
						$updated = $updateTime;
					}
				}
				$entry = new Entry(
					$article->getHref(),
					new Text((string)$article->getTitle(), Text::TYPE_HTML),
					$updated,
					$publishTime,
				);
				if ($article->hasSummary()) {
					$entry->setSummary(new Text(trim((string)$article->getSummary()), Text::TYPE_HTML));
				}
				if ($article instanceof ArticleWithText) {
					$content = Html::el();
					if ($article instanceof ArticleWithEdits && $article->getEdits()) {
						$content->addHtml(Html::el('h3')->setText($this->texyFormatter->translate('messages.blog.post.edits')));
						$edits = Html::el('ul');
						foreach ($article->getEdits() as $edit) {
							$edits->create('li')
								->addHtml(Html::el('em')
									->addHtml(Html::el('strong')->addText($edit->getEditedAt()->format('j.n.')))
									->addText(' ')
									->addHtml($edit->getSummary()));
						}
						$content->addHtml($edits);
					}
					$content->addHtml($article->getText());
					$entry->setContent(new Text(trim($content->render()), Text::TYPE_HTML));
				}
				$entry->addLink(new Link($article->getHref(), Link::REL_ALTERNATE, 'text/' . Text::TYPE_HTML));
				$feed->addEntry($entry);
				if ($updated > $feedUpdated) {
					$feedUpdated = $updated;
				}
				$type = $article::class;
				if ($article instanceof ArticleWithTags) {
					foreach ($article->getSlugTags() as $slugTag) {
						$cacheTags["{$type}/tag/{$slugTag}"] = "{$type}/tag/{$slugTag}";
					}
				}
				$cacheTags[$type] = $type;
				if ($article->hasId()) {
					$cacheTags[] = "{$type}/id/{$article->getId()}";
				}
			}
			$dependencies[Cache::Tags] = array_values($cacheTags);
			if ($feedUpdated) {
				$feed->setUpdated($feedUpdated);
			}
			return $feed;
		});
		if (!$feed instanceof Feed) {
			throw new ShouldNotHappenException(sprintf("The cached feed should be a '%s' object but is a %s", Feed::class, get_debug_type($feed)));
		}
		return $feed;
	}

}
