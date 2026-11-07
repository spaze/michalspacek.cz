<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Interviews;

use MichalSpacekCz\Sitemap\SitemapEntry;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class InterviewSitemapEntriesTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly InterviewSitemapEntries $interviewSitemapEntries,
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
			['action' => 'rozhovor'],
			['action' => 'interview'],
		]);
		Assert::equal([
			new SitemapEntry(['*' => ['name' => 'rozhovor']]),
			new SitemapEntry(['*' => ['name' => 'interview']]),
		], $this->interviewSitemapEntries->getEntries());
	}

}

TestCaseRunner::run(InterviewSitemapEntriesTest::class);
