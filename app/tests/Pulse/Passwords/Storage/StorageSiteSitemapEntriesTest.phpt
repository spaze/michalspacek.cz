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
final class StorageSiteSitemapEntriesTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly StorageSiteSitemapEntries $storageSiteSitemapEntries,
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
			['alias' => 'example.com'],
			['alias' => 'example.com/path'],
			['alias' => 'www.example.net'],
		]);
		Assert::equal([
			new SitemapEntry(['*' => ['param' => 'example.com']]),
			new SitemapEntry(['*' => ['param' => 'example.com/path']]),
			new SitemapEntry(['*' => ['param' => 'www.example.net']]),
		], $this->storageSiteSitemapEntries->getEntries());
	}

}

TestCaseRunner::run(StorageSiteSitemapEntriesTest::class);
