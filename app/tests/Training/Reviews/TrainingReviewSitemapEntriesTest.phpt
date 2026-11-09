<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Reviews;

use MichalSpacekCz\Sitemap\SitemapEntry;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Trainings\SitemapTrainings;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingReviewSitemapEntriesTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TrainingReviews $trainingReviews,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGetEntries(): void
	{
		$entries = new TrainingReviewSitemapEntries(new SitemapTrainings([
			1 => ['cs_CZ' => ['name' => 'bez-ohlasu']],
			2 => ['cs_CZ' => ['name' => 'skoleni'], 'en_US' => ['name' => 'training']],
		]), $this->trainingReviews);
		// The trainings with a review, asked once: asking again would get no rows
		$this->database->addFetchAllResult([
			['id' => 2, 'successorId' => null],
			['id' => 5, 'successorId' => null],
		]);
		Assert::equal([
			new SitemapEntry(['cs_CZ' => ['name' => 'skoleni'], 'en_US' => ['name' => 'training']]),
		], $entries->getEntries());
	}

}

TestCaseRunner::run(TrainingReviewSitemapEntriesTest::class);
