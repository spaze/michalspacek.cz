<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use DateTime;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;
use Nette\Database\Row;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingApplicationFactoryTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TrainingApplicationFactory $trainingApplicationFactory,
	) {
	}


	#[Override]
	protected function setUp(): void
	{
		$this->database->setFetchFieldDefaultResult(303);
		$this->database->setFetchPairsDefaultResult([]);
	}


	public function testCreateFromDatabaseRow(): void
	{
		$row = new Row();
		$row->id = 1;
		$row->name = null;
		$row->email = null;
		$row->familiar = 0;
		$row->company = null;
		$row->street = null;
		$row->city = null;
		$row->zip = null;
		$row->country = null;
		$row->companyId = null;
		$row->companyTaxId = null;
		$row->note = null;
		$row->status = 'ATTENDED';
		$row->statusTime = new DateTime('2024-02-03 04:05:06 +01:00');
		$row->dateId = null;
		$row->trainingId = null;
		$row->trainingAction = 'action';
		$row->trainingName = 'Le //Name//';
		$row->trainingStart = null;
		$row->trainingEnd = null;
		$row->publicDate = 1;
		$row->remote = 1;
		$row->remoteUrl = 'https://remote.example/';
		$row->remoteNotes = null;
		$row->videoHref = null;
		$row->feedbackHref = null;
		$row->venueAction = null;
		$row->venueName = null;
		$row->venueNameExtended = null;
		$row->venueAddress = null;
		$row->venueCity = null;
		$row->price = null;
		$row->vatRate = null;
		$row->priceVat = null;
		$row->discount = null;
		$row->invoiceId = null;
		$row->paid = null;
		$row->accessToken = 'token';
		$row->sourceAlias = 'michal-spacek';
		$row->sourceName = 'Michal Špaček';

		$application = $this->trainingApplicationFactory->createFromDatabaseRow($row);

		Assert::same(1, $application->getId());
		Assert::null($application->getName());
		Assert::null($application->getEmail());
		Assert::false($application->isFamiliar());
		Assert::null($application->getCompany());
		Assert::null($application->getStreet());
		Assert::null($application->getCity());
		Assert::null($application->getZip());
		Assert::null($application->getCountry());
		Assert::null($application->getCompanyId());
		Assert::null($application->getCompanyTaxId());
		Assert::null($application->getNote());
		Assert::same(TrainingApplicationStatus::Attended, $application->getStatus());
		Assert::same('2024-02-03T04:05:06+01:00', $application->getStatusTime()->format(DATE_RFC3339));
		Assert::true($application->isAttended());
		Assert::false($application->isDiscarded());
		Assert::false($application->isAllowFiles());
		Assert::null($application->getDateId());
		Assert::null($application->getTrainingId());
		Assert::same('action', $application->getTrainingAction());
		Assert::same('Le <em>Name</em>', $application->getTrainingName()->render());
		Assert::null($application->getTrainingStart());
		Assert::null($application->getTrainingEnd());
		Assert::true($application->isPublicDate());
		Assert::true($application->isRemote());
		Assert::same('https://remote.example/', $application->getRemoteUrl());
		Assert::null($application->getRemoteNotes());
		Assert::null($application->getVideoHref());
		Assert::null($application->getFeedbackHref());
		Assert::null($application->getVenueAction());
		Assert::null($application->getVenueName());
		Assert::null($application->getVenueNameExtended());
		Assert::null($application->getVenueAddress());
		Assert::null($application->getVenueCity());
		Assert::null($application->getPrice());
		Assert::null($application->getVatRate());
		Assert::null($application->getPriceVat());
		Assert::same('', $application->getPriceWithCurrency());
		Assert::same('', $application->getPriceVatWithCurrency());
		Assert::null($application->getDiscount());
		Assert::null($application->getInvoiceId());
		Assert::null($application->getPaid());
		Assert::same('token', $application->getAccessToken());
		Assert::same('michal-spacek', $application->getSourceAlias());
		Assert::same('Michal Špaček', $application->getSourceName());
		Assert::same('MŠ', $application->getSourceNameInitials());
	}

}

TestCaseRunner::run(TrainingApplicationFactoryTest::class);
