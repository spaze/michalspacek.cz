<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tags;

use DateTime;
use MichalSpacekCz\Sitemap\SitemapEntry;
use MichalSpacekCz\Test\Articles\ArticlesMock;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class TagSitemapEntriesTest extends TestCase
{

	public function __construct(
		private readonly ArticlesMock $articles,
		private readonly TagSitemapEntries $tagSitemapEntries,
		private readonly Database $database,
		private readonly Tags $tags,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->articles->reset();
		$this->database->reset();
	}


	public function testGetEntries(): void
	{
		$this->articles->setAllTags(['foo' => 'Foo', 'bar-baz' => 'Bar baz']);
		$published = new DateTime('2020-01-01 00:00:00');
		$this->database->setFetchAllDefaultResult([
			['translationGroupId' => 1, 'locale' => 'cs_CZ', 'published' => $published, 'slugTags' => $this->tags->serialize(['bar-baz'])],
			['translationGroupId' => 1, 'locale' => 'en_US', 'published' => $published, 'slugTags' => $this->tags->serialize(['waldo'])],
		]);
		Assert::equal([
			new SitemapEntry(['cs_CZ' => ['tag' => 'foo']]),
			new SitemapEntry(['cs_CZ' => ['tag' => 'bar-baz'], 'en_US' => ['tag' => 'waldo']]),
		], $this->tagSitemapEntries->getEntries());
	}

}

TestCaseRunner::run(TagSitemapEntriesTest::class);
