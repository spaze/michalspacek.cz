<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use DateTime;
use MichalSpacekCz\Test\Database\Database;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class UpcomingTrainingDatesTest extends TestCase
{

	public function __construct(
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly Database $database,
	) {
	}


	public function testGetAllUpcoming(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'dateId' => 1,
				'action' => 'action-1',
				'name' => 'Action 1',
				'status' => 'CONFIRMED',
				'start' => new DateTime('2020-01-05 04:03:02'),
				'end' => new DateTime('2020-01-07 04:03:02'),
				'labelJson' => '{"cs_CZ": "lej-bl", "en_US": "la-bel"}',
				'public' => true,
				'remote' => true,
				'venueId' => 1,
				'venueName' => 'Le venue 1',
				'venueCity' => 'Le city 1',
				'note' => 'Note 1',
			],
			[
				'dateId' => 2,
				'action' => 'action-2',
				'name' => 'Action 2',
				'status' => 'CONFIRMED',
				'start' => new DateTime('2020-02-05 04:03:02'),
				'end' => new DateTime('2020-02-07 04:03:02'),
				'labelJson' => '{"cs_CZ": "lej-bl", "en_US": "la-bel"}',
				'public' => true,
				'remote' => true,
				'venueId' => 2,
				'venueName' => 'Le venue 2',
				'venueCity' => 'Le city 2',
				'note' => 'Note 2',
			],
			[
				'dateId' => 3,
				'action' => 'action-1',
				'name' => 'Action 1',
				'status' => 'CONFIRMED',
				'start' => new DateTime('2020-03-05 04:03:02'),
				'end' => new DateTime('2020-03-07 04:03:02'),
				'labelJson' => '{"cs_CZ": "lej-bl", "en_US": "la-bel"}',
				'public' => true,
				'remote' => true,
				'venueId' => 3,
				'venueName' => 'Le venue 3',
				'venueCity' => 'Le city 3',
				'note' => 'Note 3',
			],
			[
				'dateId' => 4,
				'action' => 'action-2',
				'name' => 'Action 2',
				'status' => 'CONFIRMED',
				'start' => new DateTime('2020-04-05 04:03:02'),
				'end' => new DateTime('2020-04-07 04:03:02'),
				'labelJson' => '{"cs_CZ": "lej-bl", "en_US": "la-bel"}',
				'public' => true,
				'remote' => true,
				'venueId' => 4,
				'venueName' => 'Le venue 4',
				'venueCity' => 'Le city 4',
				'note' => 'Note 4',
			],
		]);
		$upcoming = $this->upcomingTrainingDates->getAllUpcoming();
		Assert::count(2, $upcoming);
		Assert::hasKey('action-1', $upcoming);
		Assert::hasKey('action-2', $upcoming);
		Assert::count(2, $upcoming['action-1']['dates']);
		Assert::true(isset($upcoming['action-1']['dates'][1]));
		Assert::true(isset($upcoming['action-1']['dates'][3]));
		Assert::count(2, $upcoming['action-2']['dates']);
		Assert::true(isset($upcoming['action-2']['dates'][2]));
		Assert::true(isset($upcoming['action-2']['dates'][4]));
	}

}

$runner->run(UpcomingTrainingDatesTest::class);
