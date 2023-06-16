<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use DateTime;
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
			Row::from(['lastFreeSeats' => false]),
			Row::from(['lastFreeSeats' => false]),
			Row::from(['lastFreeSeats' => false]),
		];
		Assert::false($this->freeSeats->lastFreeSeatsAnyDate($rows));

		$rows = [
			Row::from(['lastFreeSeats' => false]),
			Row::from(['lastFreeSeats' => true]),
			Row::from(['lastFreeSeats' => false]),
		];
		Assert::true($this->freeSeats->lastFreeSeatsAnyDate($rows));
	}

}

$runner->run(FreeSeatsTest::class);
