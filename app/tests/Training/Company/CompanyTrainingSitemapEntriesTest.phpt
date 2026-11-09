<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Company;

use MichalSpacekCz\Sitemap\SitemapEntry;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class CompanyTrainingSitemapEntriesTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly CompanyTrainingSitemapEntries $companyTrainingSitemapEntries,
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
			['id' => 1, 'locale' => 'cs_CZ', 'action' => 'firemni'],
			['id' => 1, 'locale' => 'en_US', 'action' => 'inhouse'],
			['id' => 2, 'locale' => 'en_US', 'action' => 'english-only'],
		]);
		Assert::equal([
			new SitemapEntry(['cs_CZ' => ['name' => 'firemni'], 'en_US' => ['name' => 'inhouse']]),
			new SitemapEntry(['en_US' => ['name' => 'english-only']]),
		], $this->companyTrainingSitemapEntries->getEntries());
	}

}

TestCaseRunner::run(CompanyTrainingSitemapEntriesTest::class);
