<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use DateTime;
use MichalSpacekCz\Test\Database\Database;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingDatesTest extends TestCase
{

	public function __construct(
		private readonly TrainingDates $trainingDates,
		private readonly Database $database,
	) {
	}


	protected function setUp(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'dateId' => 10,
				'trainingId' => 1,
				'action' => 'action-1',
				'name' => 'Action 1',
				'price' => 1000,
				'studentDiscount' => null,
				'hasCustomPrice' => true,
				'hasCustomStudentDiscount' => false,
				'start' => new DateTime('2020-01-05 04:03:02'),
				'end' => new DateTime('2020-01-07 04:03:02'),
				'labelJson' => '{"cs_CZ": "lej-bl", "en_US": "la-bel"}',
				'public' => false,
				'status' => 'CONFIRMED',
				'remote' => true,
				'remoteUrl' => null,
				'remoteNotes' => null,
				'venueId' => 1,
				'venueAction' => 'venue-1',
				'venueHref' => 'https://venue1.example/',
				'venueName' => 'Le venue 1',
				'venueNameExtended' => null,
				'venueAddress' => null,
				'venueDescription' => null,
				'venueCity' => 'Le city 1',
				'cooperationId' => null,
				'cooperationDescription' => null,
				'videoHref' => null,
				'feedbackHref' => null,
				'note' => 'Note 1',
			],
			[
				'dateId' => 20,
				'trainingId' => 1,
				'action' => 'action-2',
				'name' => 'Action 2',
				'price' => 2000,
				'studentDiscount' => null,
				'hasCustomPrice' => false,
				'hasCustomStudentDiscount' => false,
				'start' => new DateTime('2020-02-05 04:03:02'),
				'end' => new DateTime('2020-02-07 04:03:02'),
				'labelJson' => null,
				'public' => true,
				'status' => 'CONFIRMED',
				'remote' => true,
				'remoteUrl' => null,
				'remoteNotes' => null,
				'venueId' => 2,
				'venueAction' => 'venue-2',
				'venueHref' => 'https://venue2.example/',
				'venueName' => 'Le venue 2',
				'venueNameExtended' => null,
				'venueAddress' => null,
				'venueDescription' => null,
				'venueCity' => 'Le city 2',
				'cooperationId' => null,
				'cooperationDescription' => null,
				'videoHref' => null,
				'feedbackHref' => null,
				'note' => 'Note 2',
			],
		]);
	}


	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGet(): void
	{
		$this->database->setFetchResult([
			'dateId' => 1,
			'trainingId' => 1,
			'action' => 'action-1',
			'name' => 'Name',
			'price' => 2600,
			'studentDiscount' => null,
			'hasCustomPrice' => false,
			'hasCustomStudentDiscount' => false,
			'start' => new DateTime('+1 day'),
			'end' => new DateTime('+2 days'),
			'labelJson' => '{"cs_CZ": "lej-bl", "en_US": "la-bel"}',
			'public' => true,
			'status' => TrainingDateStatus::Confirmed->value,
			'remote' => false,
			'remoteUrl' => null,
			'remoteNotes' => null,
			'venueId' => 1,
			'venueAction' => 'venue-1',
			'venueHref' => 'https://venue.example',
			'venueName' => 'Venue name',
			'venueNameExtended' => 'Venue name extended',
			'venueAddress' => 'Address',
			'venueCity' => 'City',
			'venueDescription' => 'Venue **description**',
			'cooperationId' => 1,
			'cooperationDescription' => 'Co-op',
			'videoHref' => 'https://video.example',
			'feedbackHref' => 'https://feedback.example',
			'note' => 'Not-E',
		]);
		$trainingDate = $this->trainingDates->get(1);
		Assert::same('Name', $trainingDate->getName());
		Assert::same('lej-bl', $trainingDate->getLabel());
		Assert::same('Not-E', $trainingDate->getNote());
	}


	public function testGetAllTrainingsInterval(): void
	{
		$dates = $this->trainingDates->getAllTrainingsInterval('yesterday', 'tomorrow');
		Assert::count(2, $dates);
		Assert::same(10, $dates[0]->getId());
		Assert::same(20, $dates[1]->getId());
	}


	public function testGetDates(): void
	{
		$dates = $this->trainingDates->getDates(1);
		Assert::count(2, $dates);
	}


	public function testGetWithUnpaid(): void
	{
		$this->database->setFetchFieldResult(rand());
		$dates = $this->trainingDates->getWithUnpaid();
		Assert::count(2, $dates);
		Assert::same(10, $dates[0]->getId());
		Assert::same(20, $dates[1]->getId());
	}


	public function testGetAllTrainings(): void
	{
		$dates = $this->trainingDates->getAllTrainings();
		Assert::count(2, $dates);
		Assert::same(10, $dates[0]->getId());
		Assert::same(20, $dates[1]->getId());
		Assert::same('lej-bl', $dates[0]->getLabel());
		Assert::null($dates[1]->getLabel());
	}


	public function testGetPastWithPersonalData(): void
	{
		$dates = $this->trainingDates->getPastWithPersonalData();
		Assert::count(2, $dates);
		Assert::same(10, $dates[0]->getId());
		Assert::same(20, $dates[1]->getId());
	}


	/**
	 * @return array<array{start:DateTime, status:TrainingDateStatus, lastFreeSeats:bool}>
	 */
	public function getDateStartStatus(): array
	{
		return [
			[
				'start' => new DateTime('-2 days'),
				'status' => TrainingDateStatus::Confirmed,
				'lastFreeSeats' => false,
			],
			[
				'start' => new DateTime('+10 days'),
				'status' => TrainingDateStatus::Confirmed,
				'lastFreeSeats' => false,
			],
			[
				'start' => new DateTime('+2 days'),
				'status' => TrainingDateStatus::Confirmed,
				'lastFreeSeats' => true,
			],
			[
				'start' => new DateTime('+2 days'),
				'status' => TrainingDateStatus::Tentative,
				'lastFreeSeats' => false,
			],
		];
	}


	/**
	 * @dataProvider getDateStartStatus
	 */
	public function testLastFreeSeats(DateTime $start, TrainingDateStatus $status, bool $lastFreeSeats): void
	{
		$this->database->setFetchResult([
			'dateId' => 1,
			'trainingId' => 1,
			'action' => 'action-1',
			'name' => 'Name',
			'price' => 2600,
			'studentDiscount' => null,
			'hasCustomPrice' => false,
			'hasCustomStudentDiscount' => false,
			'start' => $start,
			'end' => new DateTime('+2 days'),
			'labelJson' => null,
			'public' => true,
			'status' => $status->value,
			'remote' => true,
			'remoteUrl' => null,
			'remoteNotes' => null,
			'venueId' => null,
			'venueAction' => null,
			'venueHref' => null,
			'venueName' => null,
			'venueNameExtended' => null,
			'venueAddress' => null,
			'venueCity' => null,
			'venueDescription' => null,
			'cooperationId' => null,
			'cooperationDescription' => null,
			'videoHref' => null,
			'feedbackHref' => null,
			'note' => null,
		]);
		$trainingDate = $this->trainingDates->get(1);
		Assert::same($lastFreeSeats, $trainingDate->isLastFreeSeats());
	}

}

$runner->run(TrainingDatesTest::class);
