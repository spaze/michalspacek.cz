<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tags;

use MichalSpacekCz\Sitemap\SitemapEntry;
use MichalSpacekCz\Test\Articles\ArticlesMock;
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
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->articles->reset();
	}


	public function testGetEntries(): void
	{
		$this->articles->setAllTags(['foo' => 'Foo', 'bar-baz' => 'Bar baz']);
		Assert::equal([
			new SitemapEntry(['cs_CZ' => ['tag' => 'foo']]),
			new SitemapEntry(['cs_CZ' => ['tag' => 'bar-baz']]),
		], $this->tagSitemapEntries->getEntries());
	}

}

TestCaseRunner::run(TagSitemapEntriesTest::class);
