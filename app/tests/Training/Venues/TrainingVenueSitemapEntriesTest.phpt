<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Venues;

use MichalSpacekCz\Sitemap\SitemapEntry;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingVenueSitemapEntriesTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TrainingVenueSitemapEntries $trainingVenueSitemapEntries,
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
			['action' => 'venue'],
		]);
		Assert::equal([
			new SitemapEntry(['*' => ['name' => 'venue']]),
		], $this->trainingVenueSitemapEntries->getEntries());
	}

}

TestCaseRunner::run(TrainingVenueSitemapEntriesTest::class);
