<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use DateTime;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\TrainingDateStatus;
use MichalSpacekCz\Training\Dates\UpcomingTraining;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class FreeSeatsTest extends TestCase
{

	public function __construct(
		private readonly FreeSeats $freeSeats,
	) {
	}


	public function testLastFreeSeatsAnyDate(): void
	{
		Assert::false($this->freeSeats->lastFreeSeatsAnyDate([]));

		$rows = [
			$this->buildTrainingDate(false),
			$this->buildTrainingDate(false),
			$this->buildTrainingDate(false),
		];
		Assert::false($this->freeSeats->lastFreeSeatsAnyDate($rows));

		$rows = [
			$this->buildTrainingDate(false),
			$this->buildTrainingDate(true),
			$this->buildTrainingDate(false),
		];
		Assert::true($this->freeSeats->lastFreeSeatsAnyDate($rows));
	}


	public function testLastFreeSeatsAnyTraining(): void
	{
		Assert::false($this->freeSeats->lastFreeSeatsAnyTraining([]));
		$training1 = new UpcomingTraining('action-1', 'name-1');
		$training1->addDate($this->buildTrainingDate(false));
		$training1->addDate($this->buildTrainingDate(false));
		$training1->addDate($this->buildTrainingDate(false));
		$training2 = new UpcomingTraining('action-2', 'name-2');
		$training2->addDate($this->buildTrainingDate(false));
		$training2->addDate($this->buildTrainingDate(true));
		$training2->addDate($this->buildTrainingDate(false));
		Assert::true($this->freeSeats->lastFreeSeatsAnyTraining([$training1, $training2]));
	}


	private function buildTrainingDate(bool $lastFreeSeats): TrainingDate
	{
		static $id = 1;
		return new TrainingDate(
			$id++,
			'',
			1,
			true,
			$lastFreeSeats,
			new DateTime(),
			new DateTime(),
			null,
			null,
			true,
			TrainingDateStatus::Confirmed,
			'',
			false,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			false,
			null,
			false,
			null,
			null,
			null,
			null,
		);
	}

}

$runner->run(FreeSeatsTest::class);
