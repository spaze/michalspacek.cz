<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use DateTime;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\TrainingDateStatus;
use Nette\Database\Row;
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


	public function testLastFreeSeats(): void
	{
		$row = Row::from([
			'start' => new DateTime('-2 days'),
			'status' => TrainingDateStatus::Confirmed->value,
		]);
		Assert::false($this->freeSeats->lastFreeSeats($row));
		$row->start = new DateTime('+10 days');
		Assert::false($this->freeSeats->lastFreeSeats($row));
		$row->start = new DateTime('+2 days');
		Assert::true($this->freeSeats->lastFreeSeats($row));
		$row->status = TrainingDateStatus::Tentative->value;
		Assert::false($this->freeSeats->lastFreeSeats($row));
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


	private function buildTrainingDate(bool $lastFreeSeats): TrainingDate
	{
		return new TrainingDate(
			1,
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
