<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class SitesTest extends TestCase
{

	public function __construct(
		private readonly Sites $sites,
		private readonly Database $database,
	) {
	}


	public function testGetAll(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'id' => 123,
				'url' => 'https://example.com/',
				'alias' => 'one',
			],
			[
				'id' => 456,
				'url' => 'https://com.example/',
				'alias' => 'two',
			],
		]);
		$all = $this->sites->getAll();
		Assert::same(123, $all[0]->getId());
		Assert::same('https://example.com/', $all[0]->getUrl());
		Assert::same('one', $all[0]->getAlias());
		Assert::same(456, $all[1]->getId());
		Assert::same('https://com.example/', $all[1]->getUrl());
		Assert::same('two', $all[1]->getAlias());
	}


	public function testGetByUrl(): void
	{
		$this->database->setFetchDefaultResult([
			'id' => 123,
			'url' => 'https://example.com/',
			'alias' => 'one',
		]);
		$site = $this->sites->getByUrl('foo');
		if (!$site) {
			Assert::fail('Site should not be null');
		} else {
			Assert::same(123, $site->getId());
			Assert::same('https://example.com/', $site->getUrl());
			Assert::same('one', $site->getAlias());
		}
	}

}

TestCaseRunner::run(SitesTest::class);
