<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\Sitemap\SitemapEntry;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class BlogPostSitemapEntriesTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly DateTimeMachineFactory $dateTimeFactory,
		private readonly BlogPostSitemapEntries $blogPostSitemapEntries,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->dateTimeFactory->setDateTime(null);
	}


	public function testGetEntries(): void
	{
		$now = new DateTimeImmutable('2026-11-07 12:34:56 +01:00');
		$this->dateTimeFactory->setDateTime($now);
		$this->database->setFetchAllDefaultResult([
			['slug' => 'clanek', 'locale' => 'cs_CZ', 'translationGroupId' => 5, 'lastModified' => new DateTime('2026-11-01 10:00:00 +01:00')],
			['slug' => 'jen-cesky', 'locale' => 'cs_CZ', 'translationGroupId' => null, 'lastModified' => new DateTime('2026-10-01 10:00:00 +02:00')],
			['slug' => 'article', 'locale' => 'en_US', 'translationGroupId' => 5, 'lastModified' => new DateTime('2026-11-02 10:00:00 +01:00')],
			['slug' => 'jen-cesky', 'locale' => 'en_US', 'translationGroupId' => null, 'lastModified' => new DateTime('2026-09-01 10:00:00 +02:00')],
		]);
		Assert::equal([
			new SitemapEntry(
				['cs_CZ' => ['slug' => 'clanek'], 'en_US' => ['slug' => 'article']],
				['cs_CZ' => new DateTime('2026-11-01 10:00:00 +01:00'), 'en_US' => new DateTime('2026-11-02 10:00:00 +01:00')],
			),
			new SitemapEntry(['cs_CZ' => ['slug' => 'jen-cesky']], ['cs_CZ' => new DateTime('2026-10-01 10:00:00 +02:00')]),
			new SitemapEntry(['en_US' => ['slug' => 'jen-cesky']], ['en_US' => new DateTime('2026-09-01 10:00:00 +02:00')]),
		], $this->blogPostSitemapEntries->getEntries());
		Assert::same(['2026-11-07 12:34:56'], $this->database->getParamsForQueryContaining('FROM blog_posts bp'), 'Published by the current time');
	}

}

TestCaseRunner::run(BlogPostSitemapEntriesTest::class);
