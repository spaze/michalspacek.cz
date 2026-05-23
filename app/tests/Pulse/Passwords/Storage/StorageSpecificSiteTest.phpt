<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use DateTime;
use MichalSpacekCz\Pulse\Company;
use MichalSpacekCz\Pulse\Passwords\Algorithms\PasswordHashingAlgorithm;
use MichalSpacekCz\Pulse\Passwords\Rating;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class StorageSpecificSiteTest extends TestCase
{

	public function __construct(
		private readonly Rating $rating,
	) {
	}


	public function testGetSharedWith(): void
	{
		$sharedA = new StorageSharedWith('https://a.example.com/', 'shared-a');
		$sharedB = new StorageSharedWith('https://b.example.com/', 'shared-b');
		$disclosure = new StorageDisclosure(1, 'https://example.com/', 'https://archive.example.com', null, new DateTime('yesterday'), new DateTime(), 'docs', 'docs');
		$algorithm = new StorageAlgorithm('1', new PasswordHashingAlgorithm(21, 'foo', 'bcrypt', true, true), new DateTime(), true, new StorageAlgorithmAttributes(null, null, null), null, $disclosure);
		$site = new StorageSpecificSite(
			$this->rating,
			'site-1',
			'https://example.com/',
			'example',
			[$sharedA, $sharedB],
			new Company(1, 'Foo Inc.', null, 'foo', 'Foo'),
			'storage-1',
			$algorithm,
		);
		Assert::same([$sharedA, $sharedB], $site->getSharedWith());
	}

}

TestCaseRunner::run(StorageSpecificSiteTest::class);
