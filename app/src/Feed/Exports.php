<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Feed;

use Contributte\Translation\Translator;
use DateTime;
use MichalSpacekCz\Articles\Articles;
use MichalSpacekCz\Articles\Components\ArticleWithTags;
use MichalSpacekCz\Articles\Components\ArticleWithTextAndEdits;
use MichalSpacekCz\Articles\Components\ArticleWithUpdateTime;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\BadRequestException;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\Html;
use Spaze\Exports\Atom\AtomFeed;
use Spaze\Exports\Atom\Constructs\AtomPerson;
use Spaze\Exports\Atom\Constructs\AtomText;
use Spaze\Exports\Atom\Constructs\AtomTextType;
use Spaze\Exports\Atom\Elements\AtomEntry;
use Spaze\Exports\Atom\Elements\AtomLink;
use Spaze\Exports\Atom\Elements\AtomLinkRel;

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


	public function getArticles(string $self, ?string $filter = null): AtomFeed
	{
		$key = sprintf('Atom/%s/%s', $this->translator->getDefaultLocale(), $filter !== null ? "ArticlesByTag/{$filter}" : 'AllArticles');
		$feed = $this->cache->load($key, function (array|null &$dependencies) use ($self, $filter): AtomFeed {
			$nearest = $filter !== null ? $this->articles->getNearestPublishDateByTags([$filter]) : $this->articles->getNearestPublishDate();
			$dependencies[Cache::Expire] = ($nearest instanceof DateTime ? $nearest->modify('+1 minute') : null);

			$title = $filter !== null ? $this->texyFormatter->translate('messages.label.articlesbytag', [$filter]) : $this->texyFormatter->translate('messages.label.allarticles');
			$feed = new AtomFeed($self, "Michal Špaček: {$title}");
			$feed->setLinkSelf($self);
			$feed->setAuthor(new AtomPerson('Michal Špaček'));

			$articles = $filter !== null ? $this->articles->getAllByTags([$filter], self::ITEMS) : $this->articles->getAll(self::ITEMS);
			if ($articles === []) {
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
				$entry = new AtomEntry(
					$article->getHref(),
					new AtomText((string)$article->getTitle(), AtomTextType::Html),
					$updated,
					$publishTime,
				);
				if ($article->hasSummary()) {
					$entry->setSummary(new AtomText(trim((string)$article->getSummary()), AtomTextType::Html));
				}
				if ($article instanceof ArticleWithTextAndEdits) {
					$content = Html::el();
					if ($article->getEdits() !== []) {
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
					$entry->setContent(new AtomText(trim($content->render()), AtomTextType::Html));
				}
				$entry->addLink(new AtomLink($article->getHref(), AtomLinkRel::Alternate, 'text/' . AtomTextType::Html->value));
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
			if ($feedUpdated !== null) {
				$feed->setUpdated($feedUpdated);
			}
			return $feed;
		});
		if (!$feed instanceof AtomFeed) {
			throw new ShouldNotHappenException(sprintf("The cached feed should be a '%s' object but is a %s", AtomFeed::class, get_debug_type($feed)));
		}
		return $feed;
	}

}
