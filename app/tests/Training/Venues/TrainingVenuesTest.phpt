<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Venues;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\Exceptions\TrainingVenueNotFoundException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingVenuesTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TrainingVenues $trainingVenues,
	) {
	}


	public function testGet(): void
	{
		Assert::exception(function (): void {
			$this->trainingVenues->get('foo');
		}, TrainingVenueNotFoundException::class, "Training venue 'foo' doesn't exist");


		$this->database->setFetchResult([
			'id' => 1,
			'name' => 'Name',
			'nameExtended' => null,
			'href' => 'https://venue.example/',
			'address' => 'Address',
			'city' => 'City',
			'descriptionTexy' => '//Le// **Description**',
			'action' => 'name',
			'entrance' => null,
			'entranceNavigation' => null,
			'streetview' => null,
			'parkingTexy' => '**Par** //king//',
			'publicTransportTexy' => 'Public **transport**',
		]);
		$venue = $this->trainingVenues->get('name');
		Assert::same('Name', $venue->getName());
		Assert::null($venue->getNameExtended());
		Assert::same('https://venue.example/', $venue->getHref());
		Assert::same('Address', $venue->getAddress());
		Assert::same('City', $venue->getCity());
		Assert::same('<em>Le</em> <strong>Description</strong>', $venue->getDescription()?->render());
		Assert::same('//Le// **Description**', $venue->getDescriptionTexy());
		Assert::same('name', $venue->getAction());
		Assert::null($venue->getEntrance());
		Assert::null($venue->getEntranceNavigation());
		Assert::null($venue->getStreetview());
		Assert::same('<strong>Par</strong> <em>king</em>', $venue->getParking()?->render());
		Assert::same('**Par** //king//', $venue->getParkingTexy());
		Assert::same('Public <strong>transport</strong>', $venue->getPublicTransport()?->render());
		Assert::same('Public **transport**', $venue->getPublicTransportTexy());
	}

}

TestCaseRunner::run(TrainingVenuesTest::class);
