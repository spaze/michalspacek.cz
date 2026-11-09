<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use MichalSpacekCz\Sitemap\SitemapEntry;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class TalkSitemapEntriesTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TalkSitemapEntries $talkSitemapEntries,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGetEntries(): void
	{
		$this->database->setFetchAllDefaultResult([
			['action' => 'prednaska', 'locale' => 'cs_CZ', 'translationGroupId' => 3],
			['action' => 'jen-cesky', 'locale' => 'cs_CZ', 'translationGroupId' => null],
			['action' => 'talk', 'locale' => 'en_US', 'translationGroupId' => 3],
			['action' => 'english-only', 'locale' => 'en_US', 'translationGroupId' => null],
		]);
		Assert::equal([
			new SitemapEntry(['cs_CZ' => ['name' => 'prednaska'], 'en_US' => ['name' => 'talk']]),
			new SitemapEntry(['cs_CZ' => ['name' => 'jen-cesky']]),
			new SitemapEntry(['en_US' => ['name' => 'english-only']]),
		], $this->talkSitemapEntries->getEntries());
	}

}

TestCaseRunner::run(TalkSitemapEntriesTest::class);
