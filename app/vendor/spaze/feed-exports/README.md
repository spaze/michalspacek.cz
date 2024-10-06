# feed-exports
Atom feed Response and related objects for Nette framework

## Nette Framework usage

```php
use Spaze\Exports\Atom\Constructs\Person;
use Spaze\Exports\Atom\Constructs\Text;
use Spaze\Exports\Atom\Elements\Entry;
use Spaze\Exports\Atom\Feed;
use Spaze\Exports\Bridges\Nette\Atom\Response;

// [ ... ]

    public function actionArticles(?string $param = null): void
    {
        $now = new \DateTimeImmutable('2020-10-20 10:20:20 Europe/Prague');

        $feed = new Feed('https://url', 'Feed Title');
        $feed->setLinkSelf('https://url');
        $feed->setUpdated($now);
        $feed->setAuthor(new Person('foo bar'));

        $entry = new Entry(
            'https://href/1',
            new Text('<em>title-1</em>', Text::TYPE_HTML),
            new \DateTimeImmutable('2019-12-20 12:20:20 Europe/Prague'),
            new \DateTimeImmutable('2019-12-16 12:20:20 Europe/Prague')
        );
        $entry->setContent(new Text('some <strong>content-1</strong>'));
        $feed->addEntry($entry);

        $entry = new Entry(
            'https://href/2',
            new Text('title-2', Text::TYPE_TEXT),
            new \DateTimeImmutable('2018-12-20 12:20:20 Europe/Prague'),
            new \DateTimeImmutable('2018-12-16 12:20:20 Europe/Prague')
        );
        $entry->setContent(new Text('other <strong>content-2</strong>'));
        $feed->addEntry($entry);

        $this->sendResponse(new Response($feed));
    }

// [ ... ]
```