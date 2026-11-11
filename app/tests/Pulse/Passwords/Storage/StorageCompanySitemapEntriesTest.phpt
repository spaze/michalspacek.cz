<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use MichalSpacekCz\Sitemap\SitemapEntry;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class StorageCompanySitemapEntriesTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly StorageCompanySitemapEntries $storageCompanySitemapEntries,
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
			['alias' => 'example'],
			['alias' => 'example-two'],
		]);
		Assert::equal([
			new SitemapEntry(['*' => ['param' => 'example']]),
			new SitemapEntry(['*' => ['param' => 'example-two']]),
		], $this->storageCompanySitemapEntries->getEntries());
	}

}

TestCaseRunner::run(StorageCompanySitemapEntriesTest::class);
