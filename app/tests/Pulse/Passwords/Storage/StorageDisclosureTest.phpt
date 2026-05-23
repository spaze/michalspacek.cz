<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use DateTime;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class StorageDisclosureTest extends TestCase
{

	public function testGetters(): void
	{
		$published = new DateTime('yesterday');
		$added = new DateTime();
		$disclosure = new StorageDisclosure(42, 'https://example.com/', 'https://archive.example.com', 'a note', $published, $added, 'docs', 'docs-alias');
		Assert::same(42, $disclosure->getId());
		Assert::same('https://example.com/', $disclosure->getUrl());
		Assert::same('https://archive.example.com', $disclosure->getArchive());
		Assert::same('a note', $disclosure->getNote());
		Assert::same($published, $disclosure->getPublished());
		Assert::same($added, $disclosure->getAdded());
		Assert::same('docs', $disclosure->getType());
		Assert::same('docs-alias', $disclosure->getTypeAlias());
	}

}

TestCaseRunner::run(StorageDisclosureTest::class);
