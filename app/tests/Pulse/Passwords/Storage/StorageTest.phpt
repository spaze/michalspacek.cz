<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use MichalSpacekCz\Pulse\Passwords\SearchResult;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class StorageTest extends TestCase
{

	public function testGetters(): void
	{
		$storage = new Storage('storage-1', 42);
		Assert::same('storage-1', $storage->getId());
		Assert::same(42, $storage->getCompanyId());
		Assert::type(SearchResult::class, $storage->getSearchResult());
	}

}

TestCaseRunner::run(StorageTest::class);
