<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Trainings;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class SitemapTrainingsFactoryTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly SitemapTrainingsFactory $sitemapTrainingsFactory,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testCreate(): void
	{
		// The URLs of every training, 3 isn't one Trainings::getNames() lists
		$this->database->addFetchAllResult([
			['trainingId' => 1, 'language' => 'cs_CZ', 'action' => 'skoleni'],
			['trainingId' => 1, 'language' => 'en_US', 'action' => 'training'],
			['trainingId' => 2, 'language' => 'cs_CZ', 'action' => 'jen-cesky'],
			['trainingId' => 3, 'language' => 'cs_CZ', 'action' => 'ukonceno'],
		]);
		// The trainings, 4 lost its URLs after they were read
		$this->database->addFetchAllResult([
			$this->getTrainingRow(1, 'skoleni'),
			$this->getTrainingRow(2, 'jen-cesky'),
			$this->getTrainingRow(4, 'bez-adresy'),
		]);
		Assert::same([
			1 => ['cs_CZ' => ['name' => 'skoleni'], 'en_US' => ['name' => 'training']],
			2 => ['cs_CZ' => ['name' => 'jen-cesky']],
			4 => [],
		], $this->sitemapTrainingsFactory->create()->getLocaleParams());
	}


	/**
	 * @return array<string, int|string|null>
	 */
	private function getTrainingRow(int $id, string $action): array
	{
		return [
			'id' => $id,
			'action' => $action,
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
		];
	}

}

TestCaseRunner::run(SitemapTrainingsFactoryTest::class);
