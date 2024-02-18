<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Preliminary;

use DateTime;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class PreliminaryTrainingsTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly PreliminaryTrainings $preliminaryTrainings,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGetPreliminary(): void
	{
		$this->setDatabaseResultsForGetPreliminary();
		$preliminaryTrainings = $this->preliminaryTrainings->getPreliminary();
		Assert::count(2, $preliminaryTrainings);
		$training1Applications = $preliminaryTrainings[0]->getApplications();
		Assert::count(1, $training1Applications);
		$training2Applications = $preliminaryTrainings[1]->getApplications();
		Assert::count(1, $training2Applications);
		Assert::same('Name One', $training1Applications[0]->getName());
		Assert::same('Name Two', $training2Applications[0]->getName());
	}


	public function testGetPreliminaryCountsGetPreliminaryWithDateSet(): void
	{
		$this->setDatabaseResultsForGetPublicUpcoming();
		$this->setDatabaseResultsForGetPreliminary();
		Assert::same([2, 1], $this->preliminaryTrainings->getPreliminaryCounts());

		// Test getPreliminaryWithDateSet() here because UpcomingTrainingDates::getPublicUpcoming() result is request-cached
		$this->setDatabaseResultsForGetPreliminary();
		$trainingApplications = $this->preliminaryTrainings->getPreliminaryWithDateSet();
		Assert::count(1, $trainingApplications);
		Assert::same('Name One', $trainingApplications[0]->getName());
	}


	private function setDatabaseResultsForGetPreliminary(): void
	{
		$this->database->addFetchAllResult([
			[
				'idTraining' => 1,
				'action' => 'action-1',
				'name' => 'Training 1',
			],
			[
				'idTraining' => 2,
				'action' => 'action-2',
				'name' => 'Training 2',
			],
		]);
		$this->database->setFetchFieldDefaultResult(303); // For ApplicationStatuses::getStatusId()
		$this->database->addFetchAllResult([
			[
				'id' => 1,
				'name' => 'Name One',
				'email' => null,
				'familiar' => 0,
				'company' => null,
				'street' => null,
				'city' => null,
				'zip' => null,
				'country' => null,
				'companyId' => null,
				'companyTaxId' => null,
				'note' => null,
				'status' => 'ATTENDED',
				'statusTime' => new DateTime(),
				'dateId' => null,
				'trainingId' => 1,
				'trainingAction' => 'action-1',
				'trainingName' => 'Le //Name//',
				'trainingStart' => null,
				'trainingEnd' => null,
				'publicDate' => 1,
				'remote' => 1,
				'remoteUrl' => 'https://remote.example/',
				'remoteNotes' => null,
				'videoHref' => null,
				'feedbackHref' => null,
				'venueAction' => null,
				'venueName' => null,
				'venueNameExtended' => null,
				'venueAddress' => null,
				'venueCity' => null,
				'price' => null,
				'vatRate' => null,
				'priceVat' => null,
				'discount' => null,
				'invoiceId' => null,
				'paid' => null,
				'accessToken' => 'token',
				'sourceAlias' => 'michal-spacek',
				'sourceName' => 'Michal Špaček',
			],
			[
				'id' => 2,
				'name' => 'Name Two',
				'email' => null,
				'familiar' => 0,
				'company' => null,
				'street' => null,
				'city' => null,
				'zip' => null,
				'country' => null,
				'companyId' => null,
				'companyTaxId' => null,
				'note' => null,
				'status' => 'ATTENDED',
				'statusTime' => new DateTime(),
				'dateId' => null,
				'trainingId' => 2,
				'trainingAction' => 'action-2',
				'trainingName' => 'Le //Name//',
				'trainingStart' => null,
				'trainingEnd' => null,
				'publicDate' => 1,
				'remote' => 1,
				'remoteUrl' => 'https://remote.example/',
				'remoteNotes' => null,
				'videoHref' => null,
				'feedbackHref' => null,
				'venueAction' => null,
				'venueName' => null,
				'venueNameExtended' => null,
				'venueAddress' => null,
				'venueCity' => null,
				'price' => null,
				'vatRate' => null,
				'priceVat' => null,
				'discount' => null,
				'invoiceId' => null,
				'paid' => null,
				'accessToken' => 'token',
				'sourceAlias' => 'michal-spacek',
				'sourceName' => 'Michal Špaček',
			],
		]);
	}


	private function setDatabaseResultsForGetPublicUpcoming(): void
	{
		$this->database->addFetchAllResult([
			[
				'dateId' => 1,
				'trainingId' => 1,
				'action' => 'action-1',
				'name' => 'Action 1',
				'price' => 3490,
				'studentDiscount' => null,
				'hasCustomPrice' => false,
				'hasCustomStudentDiscount' => false,
				'start' => new DateTime(),
				'end' => new DateTime(),
				'labelJson' => null,
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
				'dateId' => 3,
				'trainingId' => 3,
				'action' => 'action-3',
				'name' => 'Action 3',
				'price' => 4490,
				'studentDiscount' => null,
				'hasCustomPrice' => false,
				'hasCustomStudentDiscount' => false,
				'start' => new DateTime(),
				'end' => new DateTime(),
				'labelJson' => null,
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
	}

}

TestCaseRunner::run(PreliminaryTrainingsTest::class);
