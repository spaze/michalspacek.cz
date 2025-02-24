<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use DateTime;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Database\Row;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingDateFactoryTest extends TestCase
{

	public function __construct(
		private readonly TrainingDateFactory $trainingDateFactory,
	) {
	}


	public function testGet(): void
	{
		$row = new Row();
		$row->dateId = 1;
		$row->trainingId = 1;
		$row->action = 'action-1';
		$row->name = 'Name';
		$row->price = 2600;
		$row->studentDiscount = null;
		$row->hasCustomPrice = 0;
		$row->hasCustomStudentDiscount = 0;
		$row->start = new DateTime('+1 day');
		$row->end = new DateTime('+2 days');
		$row->labelJson = '{"cs_CZ": "lej-bl", "en_US": "la-bel"}';
		$row->public = 1;
		$row->status = TrainingDateStatus::Confirmed->value;
		$row->remote = 0;
		$row->remoteUrl = null;
		$row->remoteNotes = null;
		$row->venueId = 1;
		$row->venueAction = 'venue-1';
		$row->venueHref = 'https://venue.example';
		$row->venueName = 'Venue name';
		$row->venueNameExtended = 'Venue name extended';
		$row->venueAddress = 'Address';
		$row->venueCity = 'City';
		$row->venueDescription = 'Venue **description**';
		$row->cooperationId = 1;
		$row->cooperationDescription = 'Co-op';
		$row->videoHref = 'https://video.example';
		$row->feedbackHref = 'https://feedback.example';
		$row->note = 'Not-E';
		$trainingDate = $this->trainingDateFactory->get($row);
		Assert::same('Name', $trainingDate->getName());
		Assert::same('lej-bl', $trainingDate->getLabel());
		Assert::same('Not-E', $trainingDate->getNote());

		$row->labelJson = '{}';
		$trainingDate = $this->trainingDateFactory->get($row);
		Assert::null($trainingDate->getLabel());

		$row->labelJson = '{"cs_CZ": 303}';
		$trainingDate = $this->trainingDateFactory->get($row);
		Assert::null($trainingDate->getLabel());
	}

}

TestCaseRunner::run(TrainingDateFactoryTest::class);
