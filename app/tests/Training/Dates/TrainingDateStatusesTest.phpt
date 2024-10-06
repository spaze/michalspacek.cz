<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingDateStatusesTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TrainingDateStatuses $trainingDateStatuses,
	) {
	}


	public function testGetStatuses(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'id' => 1,
				'status' => 'CREATED',
				'description' => 'Displayed in admin only',
			],
			[
				'id' => 2,
				'status' => 'TENTATIVE',
				'description' => 'Displayed on the site as month, tentative signup',
			],
			[
				'id' => 3,
				'status' => 'CONFIRMED',
				'description' => 'Displayed on the site with full date, regular signup',
			],
			[
				'id' => 4,
				'status' => 'CANCELED',
				'description' => 'Displayed only in admin',
			],
		]);
		$statuses = $this->trainingDateStatuses->getStatuses();
		Assert::count(4, $statuses);
		Assert::same(1, $statuses[0]->id());
		Assert::same('CREATED', $statuses[0]->value);
		Assert::same(2, $statuses[1]->id());
		Assert::same('TENTATIVE', $statuses[1]->value);
		Assert::same(3, $statuses[2]->id());
		Assert::same('CONFIRMED', $statuses[2]->value);
		Assert::same(4, $statuses[3]->id());
		Assert::same('CANCELED', $statuses[3]->value);
	}


	public function testGetStatusesIdMismatch(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'id' => 2,
				'status' => 'CREATED',
				'description' => 'Displayed in admin only',
			],
		]);
		Assert::exception(function (): void {
			$this->trainingDateStatuses->getStatuses();
		}, ShouldNotHappenException::class, "Training data status enum doesn't match database values for status 'CREATED'");
	}


	public function testGetStatusesDescriptionMismatch(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'id' => 2,
				'status' => 'TENTATIVE',
				'description' => 'Foo',
			],
		]);
		Assert::exception(function (): void {
			$this->trainingDateStatuses->getStatuses();
		}, ShouldNotHappenException::class, "Training data status enum doesn't match database values for status 'TENTATIVE'");
	}

}

TestCaseRunner::run(TrainingDateStatusesTest::class);
