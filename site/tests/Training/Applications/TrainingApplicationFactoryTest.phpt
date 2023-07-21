<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use DateTime;
use MichalSpacekCz\Test\Database\Database;
use Nette\Database\Row;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingApplicationFactoryTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TrainingApplicationFactory $trainingApplicationFactory,
	) {
	}


	protected function setUp(): void
	{
		$this->database->setFetchFieldDefaultResult(303);
		$this->database->setFetchPairsResult([]);
	}


	public function testCreateFromDatabaseRow(): void
	{
		$row = Row::from([
			'id' => 1,
			'name' => null,
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
			'trainingId' => null,
			'trainingAction' => 'action',
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
		]);
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

$runner->run(TrainingApplicationFactoryTest::class);
