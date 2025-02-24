<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use DateTime;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
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
		$row->statusTime = new DateTime();
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
		Assert::false($application->isFamiliar());
		Assert::true($application->isAttended());
		Assert::false($application->isDiscarded());
		Assert::false($application->isAllowFiles());
		Assert::true($application->isPublicDate());
		Assert::true($application->isRemote());
		Assert::same('', $application->getPriceWithCurrency());
		Assert::same('', $application->getPriceVatWithCurrency());
		Assert::same('MŠ', $application->getSourceNameInitials());
	}

}

TestCaseRunner::run(TrainingApplicationFactoryTest::class);
