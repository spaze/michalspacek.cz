<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Trainings;

use MichalSpacekCz\Sitemap\SitemapEntry;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Reviews\TrainingReviewSitemapEntries;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/**
 * In a file of its own, the container keeps the list from the first time it's built.
 *
 * @testCase
 */
final class SitemapTrainingsTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TrainingSitemapEntries $trainingSitemapEntries,
		private readonly TrainingReviewSitemapEntries $trainingReviewSitemapEntries,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testBuiltOnceForTheTrainingsAndTheirReviews(): void
	{
		$this->database->addFetchAllResult([
			['trainingId' => 1, 'language' => 'cs_CZ', 'action' => 'skoleni'],
		]);
		$this->database->addFetchAllResult([
			[
				'id' => 1,
				'action' => 'skoleni',
				'name' => 'Name',
				'description' => 'Description',
				'content' => 'Content',
				'upsell' => null,
				'prerequisites' => null,
				'audience' => null,
				'capacity' => null,
				'price' => null,
				'studentDiscount' => null,
				'materials' => null,
				'custom' => 0,
				'successorId' => null,
				'discontinuedId' => null,
			],
		]);
		$expected = [new SitemapEntry(['cs_CZ' => ['name' => 'skoleni']])];
		Assert::equal($expected, $this->trainingSitemapEntries->getEntries());
		// The trainings with a review, a list built again would read them as its URLs instead
		$this->database->addFetchAllResult([
			['id' => 1, 'successorId' => null],
		]);
		Assert::equal($expected, $this->trainingReviewSitemapEntries->getEntries());
	}

}

TestCaseRunner::run(SitemapTrainingsTest::class);
