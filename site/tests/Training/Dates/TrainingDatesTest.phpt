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
class TrainingDatesFormValidatorTest extends TestCase
{

	public function __construct(
		private readonly TrainingDates $trainingDates,
		private readonly Database $database,
	) {
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
				'labelJson' => '{"cs_CZ": "lej-bl", "en_US": "la-bel"}',
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
		$dates = $this->trainingDates->getAllTrainingsInterval('yesterday', 'tomorrow');
		Assert::count(2, $dates);
		Assert::same(10, $dates[0]->getId());
		Assert::same(20, $dates[1]->getId());
	}


	public function testGetDates(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'dateId' => 10,
				'trainingId' => 1,
				'action' => 'action-1',
				'name' => 'Name 1',
				'price' => 1337,
				'studentDiscount' => 42,
				'hasCustomPrice' => true,
				'hasCustomStudentDiscount' => false,
				'start' => new DateTime('2020-01-05 04:03:02'),
				'end' => new DateTime('2020-01-07 04:03:02'),
				'labelJson' => '{"cs_CZ": "lej-bl", "en_US": "la-bel"}',
				'public' => true,
				'status' => 'CONFIRMED',
				'remote' => false,
				'remoteUrl' => null,
				'remoteNotes' => null,
				'venueId' => 303,
				'venueAction' => 'venue-1',
				'venueHref' => 'https://venue1.example',
				'venueName' => 'Le venue 1',
				'venueNameExtended' => null,
				'venueAddress' => 'Le address 1',
				'venueCity' => 'Le city 1',
				'venueDescription' => 'Le desc 1',
				'cooperationId' => null,
				'cooperationDescription' => 'Co-op',
				'videoHref' => null,
				'feedbackHref' => null,
				'note' => 'Note 1',
			],
			[
				'dateId' => 20,
				'trainingId' => 1,
				'action' => 'action-2',
				'name' => 'Name 2',
				'price' => 1337,
				'studentDiscount' => 42,
				'hasCustomPrice' => true,
				'hasCustomStudentDiscount' => false,
				'start' => new DateTime('2020-02-05 04:03:02'),
				'end' => new DateTime('2020-02-07 04:03:02'),
				'labelJson' => '{"cs_CZ": "lej-bl", "en_US": "la-bel"}',
				'public' => true,
				'status' => 'CONFIRMED',
				'remote' => true,
				'remoteUrl' => null,
				'remoteNotes' => null,
				'venueId' => 304,
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
			],
		]);
		$dates = $this->trainingDates->getDates(1);
		Assert::count(2, $dates);
	}


	public function testGetWithUnpaid(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'dateId' => 1336,
				'trainingId' => 1,
				'action' => 'action-1',
				'name' => 'Name',
				'price' => 1330,
				'studentDiscount' => null,
				'hasCustomPrice' => false,
				'hasCustomStudentDiscount' => false,
				'start' => new DateTime('2023-05-01 10:00:00'),
				'end' => new DateTime('2023-05-02 17:00:00'),
				'labelJson' => null,
				'public' => true,
				'status' => TrainingDateStatus::Confirmed->value,
				'remote' => true,
				'remoteUrl' => null,
				'remoteNotes' => 'Notes',
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
			],
			[
				'dateId' => 1337,
				'trainingId' => 1,
				'action' => 'action-1',
				'name' => 'Name',
				'price' => 1340,
				'studentDiscount' => null,
				'hasCustomPrice' => false,
				'hasCustomStudentDiscount' => false,
				'start' => new DateTime('2023-06-01 10:00:00'),
				'end' => new DateTime('2023-06-02 17:00:00'),
				'labelJson' => null,
				'public' => true,
				'status' => TrainingDateStatus::Confirmed->value,
				'remote' => true,
				'remoteUrl' => null,
				'remoteNotes' => 'Notes',
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
			],
		]);
		$this->database->setFetchFieldResult(rand());
		$dates = $this->trainingDates->getWithUnpaid();
		Assert::count(2, $dates);
		Assert::same(1336, $dates[0]->getId());
		Assert::same(1337, $dates[1]->getId());
	}

}

$runner->run(TrainingDatesFormValidatorTest::class);
