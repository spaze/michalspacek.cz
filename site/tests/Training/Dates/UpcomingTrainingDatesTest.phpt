<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use DateTime;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class UpcomingTrainingDatesTest extends TestCase
{

	public function __construct(
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly Database $database,
	) {
	}


	private function setFetchAllDefaultResult(bool $includeNonPublic): void
	{
		$result = [
			[
				'dateId' => 1,
				'trainingId' => 1,
				'action' => 'action-1',
				'name' => 'Action 1',
				'price' => 3490,
				'studentDiscount' => null,
				'hasCustomPrice' => false,
				'hasCustomStudentDiscount' => false,
				'start' => new DateTime('2020-01-05 04:03:02'),
				'end' => new DateTime('2020-01-07 04:03:02'),
				'labelJson' => '{"cs_CZ": "lej-bl", "en_US": "la-bel"}',
				'status' => 'CONFIRMED',
				'public' => false,
				'remote' => true,
				'remoteUrl' => null,
				'remoteNotes' => null,
				'venueId' => 1,
				'venueAction' => 'venue-1',
				'venueHref' => 'https://venue1.example/',
				'venueName' => 'Le venue 1',
				'venueNameExtended' => null,
				'venueAddress' => 'Street 22',
				'venueCity' => 'Le city 1',
				'venueDescription' => 'Venue description //1//',
				'cooperationId' => null,
				'cooperationDescription' => null,
				'videoHref' => null,
				'feedbackHref' => null,
				'note' => 'Note 1',
			],
			[
				'dateId' => 2,
				'trainingId' => 2,
				'action' => 'action-2',
				'name' => 'Action 2',
				'price' => 4490,
				'studentDiscount' => null,
				'hasCustomPrice' => false,
				'hasCustomStudentDiscount' => false,
				'start' => new DateTime('2020-02-05 04:03:02'),
				'end' => new DateTime('2020-02-07 04:03:02'),
				'labelJson' => '{"cs_CZ": "lej-bl", "en_US": "la-bel"}',
				'status' => 'CONFIRMED',
				'public' => true,
				'remote' => true,
				'remoteUrl' => null,
				'remoteNotes' => null,
				'venueId' => 2,
				'venueAction' => 'venue-2',
				'venueHref' => 'https://venue2.example/',
				'venueName' => 'Le venue 2',
				'venueNameExtended' => null,
				'venueAddress' => 'Street 22',
				'venueCity' => 'Le city 2',
				'venueDescription' => 'Venue description //2//',
				'cooperationId' => null,
				'cooperationDescription' => null,
				'videoHref' => null,
				'feedbackHref' => null,
				'note' => 'Note 2',
			],
			[
				'dateId' => 3,
				'trainingId' => 1,
				'action' => 'action-1',
				'name' => 'Action 1',
				'price' => 3490,
				'studentDiscount' => null,
				'hasCustomPrice' => false,
				'hasCustomStudentDiscount' => false,
				'start' => new DateTime('2020-03-05 04:03:02'),
				'end' => new DateTime('2020-03-07 04:03:02'),
				'labelJson' => '{"cs_CZ": "lej-bl", "en_US": "la-bel"}',
				'status' => 'CONFIRMED',
				'public' => true,
				'remote' => true,
				'remoteUrl' => null,
				'remoteNotes' => null,
				'venueId' => 3,
				'venueAction' => 'venue-3',
				'venueHref' => 'https://venue3.example/',
				'venueName' => 'Le venue 3',
				'venueNameExtended' => null,
				'venueAddress' => 'Street 22',
				'venueCity' => 'Le city 3',
				'venueDescription' => 'Venue description //3//',
				'cooperationId' => null,
				'cooperationDescription' => null,
				'videoHref' => null,
				'feedbackHref' => null,
				'note' => 'Note 3',
			],
			[
				'dateId' => 4,
				'trainingId' => 2,
				'action' => 'action-2',
				'name' => 'Action 2',
				'price' => 3490,
				'studentDiscount' => null,
				'hasCustomPrice' => false,
				'hasCustomStudentDiscount' => false,
				'start' => new DateTime('2020-04-05 04:03:02'),
				'end' => new DateTime('2020-04-07 04:03:02'),
				'labelJson' => '{"cs_CZ": "lej-bl", "en_US": "la-bel"}',
				'status' => 'CONFIRMED',
				'public' => false,
				'remote' => true,
				'remoteUrl' => null,
				'remoteNotes' => null,
				'venueId' => 4,
				'venueAction' => 'venue-4',
				'venueHref' => 'https://venue4.example/',
				'venueName' => 'Le venue 4',
				'venueNameExtended' => null,
				'venueAddress' => 'Street 22',
				'venueCity' => 'Le city 4',
				'venueDescription' => null,
				'cooperationId' => null,
				'cooperationDescription' => null,
				'videoHref' => null,
				'feedbackHref' => null,
				'note' => 'Note 4',
			],
		];
		$result = array_values(array_filter($result, function (array $row) use ($includeNonPublic): bool {
			return $includeNonPublic || $row['public'];
		}));
		$this->database->setFetchAllDefaultResult($result);
	}


	public function testGetAllUpcoming(): void
	{
		$this->setFetchAllDefaultResult(true);
		$upcoming = $this->upcomingTrainingDates->getAllUpcoming();
		Assert::count(2, $upcoming);
		Assert::hasKey('action-1', $upcoming);
		Assert::hasKey('action-2', $upcoming);
		Assert::count(2, $upcoming['action-1']->getDates());
		Assert::true(isset($upcoming['action-1']->getDates()[1]));
		Assert::true(isset($upcoming['action-1']->getDates()[3]));
		Assert::count(2, $upcoming['action-2']->getDates());
		Assert::true(isset($upcoming['action-2']->getDates()[2]));
		Assert::true(isset($upcoming['action-2']->getDates()[4]));
	}


	public function testGetPublicUpcoming(): void
	{
		$this->setFetchAllDefaultResult(false);
		$upcoming = $this->upcomingTrainingDates->getPublicUpcoming();
		Assert::count(2, $upcoming);
		Assert::hasKey('action-1', $upcoming);
		Assert::hasKey('action-2', $upcoming);
		Assert::count(1, $upcoming['action-1']->getDates());
		Assert::true(isset($upcoming['action-1']->getDates()[3]));
		Assert::count(1, $upcoming['action-2']->getDates());
		Assert::true(isset($upcoming['action-2']->getDates()[2]));
	}


	public function testGetPublicUpcomingIds(): void
	{
		$this->setFetchAllDefaultResult(false);
		Assert::same([2, 3], $this->upcomingTrainingDates->getPublicUpcomingIds());
	}


	public function testGetPublicUpcomingAtVenue(): void
	{
		$this->setFetchAllDefaultResult(false);
		$upcoming = $this->upcomingTrainingDates->getPublicUpcomingAtVenue(2);
		Assert::count(1, $upcoming);
		Assert::hasKey('action-2', $upcoming);
		Assert::count(1, $upcoming['action-2']->getDates());
		Assert::same(2, $upcoming['action-2']->getDates()[2]->getVenueId());
	}

}

TestCaseRunner::run(UpcomingTrainingDatesTest::class);
