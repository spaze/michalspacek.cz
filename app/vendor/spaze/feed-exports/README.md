# feed-exports
Atom feed Response and related objects for Nette framework

## Nette Framework usage

```php
use Spaze\Exports\Atom\Constructs\AtomPerson;
use Spaze\Exports\Atom\Constructs\AtomText;
use Spaze\Exports\Atom\Constructs\AtomTextType;
use Spaze\Exports\Atom\Elements\AtomEntry;
use Spaze\Exports\Atom\AtomFeed;
use Spaze\Exports\Bridges\Nette\AtomResponse;

// [ ... ]

    public function actionArticles(?string $param = null): void
    {
        $now = new \DateTimeImmutable('2020-10-20 10:20:20 Europe/Prague');

        $feed = new AtomFeed('https://url', 'Feed Title');
        $feed->setLinkSelf('https://url');
        $feed->setUpdated($now);
        $feed->setAuthor(new AtomPerson('foo bar'));

        $entry = new AtomEntry(
            'https://href/1',
            new AtomText('<em>title-1</em>', AtomTextType::Html),
            new \DateTimeImmutable('2019-12-20 12:20:20 Europe/Prague'),
            new \DateTimeImmutable('2019-12-16 12:20:20 Europe/Prague')
        );
        $entry->setContent(new AtomText('some <strong>content-1</strong>'));
        $feed->addEntry($entry);

        $entry = new AtomEntry(
            'https://href/2',
            new AtomText('title-2', AtomTextType::Text),
            new \DateTimeImmutable('2018-12-20 12:20:20 Europe/Prague'),
            new \DateTimeImmutable('2018-12-16 12:20:20 Europe/Prague')
        );
        $entry->setContent(new AtomText('other <strong>content-2</strong>'));
        $feed->addEntry($entry);

        $this->sendResponse(new AtomResponse($feed));
    }

// [ ... ]
```
