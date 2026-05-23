<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class StorageSharedWithTest extends TestCase
{

	public function testGetters(): void
	{
		$sharedWith = new StorageSharedWith('https://example.com/', 'example');
		Assert::same('https://example.com/', $sharedWith->getUrl());
		Assert::same('example', $sharedWith->getAlias());
	}

}

TestCaseRunner::run(StorageSharedWithTest::class);
