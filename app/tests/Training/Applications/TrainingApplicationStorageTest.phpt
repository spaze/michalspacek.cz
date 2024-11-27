<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use DateTime;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatuses;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\TrainingDateStatus;
use Override;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingApplicationStorageTest extends TestCase
{

	private const int STATUS_CREATED = 1;
	private const int STATUS_TENTATIVE = 2;
	private const int STATUS_SIGNED_UP = 4;
	private const int SOURCE_ID = 303;
	private const int INSERT_ID = 1337;


	public function __construct(
		private readonly Database $database,
		private readonly TrainingApplicationStorage $trainingApplicationStorage,
		private readonly TrainingApplicationStatuses $applicationStatuses,
	) {
	}


	#[Override]
	protected function setUp(): void
	{
		$this->database->setFetchPairsDefaultResult([ // For ApplicationStatuses::getInitialStatuses()
			self::STATUS_TENTATIVE => 'TENTATIVE',
			4 => 'SIGNED_UP',
			13 => 'IMPORTED',
			14 => 'NON_PUBLIC_TRAINING',
		]);
		$this->database->addFetchFieldResult(self::STATUS_CREATED); // For ApplicationStatuses::getStatusId() in TrainingApplicationStorage::insertApplication()
		$this->database->addFetchFieldResult(self::SOURCE_ID); // For TrainingApplicationSources::getSourceId in TrainingApplicationStorage::insertApplication()
		$this->database->setDefaultInsertId((string)self::INSERT_ID);
		$this->database->setFetchResult([ // For ApplicationStatuses::setStatus() in ApplicationStatuses::updateStatusCallbackReturnId()
			'statusId' => self::STATUS_CREATED,
			'statusTime' => new DateTime(),
			'statusTimeTimeZone' => 'Europe/Prague',
		]);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		PrivateProperty::setValue($this->applicationStatuses, 'statusIds', []);
	}


	public function testAddInvitation(): void
	{
		$this->database->addFetchFieldResult(self::STATUS_TENTATIVE); // For ApplicationStatuses::getStatusId() in ApplicationStatuses::setStatus()
		$id = $this->trainingApplicationStorage->addInvitation(
			$this->buildTrainingDate(),
			'Name',
			'email@example.example',
			'company',
			'street',
			'city',
			'zip',
			'country',
			'companyId',
			'',
			'note',
		);
		Assert::same(self::INSERT_ID, $id);
		$this->assertInsertApplicationParams(
			1,
			'Name',
			'company',
			'street',
			'city',
			'zip',
			'country',
			'companyId',
			null,
			'note',
			null,
			null,
			null,
			null,
			self::STATUS_TENTATIVE,
		);
	}


	public function testInsertApplicationInvalidInitialStatus(): void
	{
		Assert::exception(function (): void {
			$this->trainingApplicationStorage->insertApplication(
				1,
				null,
				'Name',
				'email@example',
				null,
				null,
				null,
				null,
				null,
				null,
				null,
				null,
				null,
				null,
				TrainingApplicationStatus::Attended,
				'michal-spacek',
			);
		}, RuntimeException::class, "Invalid initial status ATTENDED");
	}


	public function testInsertApplicationNulls(): void
	{
		$this->database->addFetchFieldResult(self::STATUS_SIGNED_UP); // For ApplicationStatuses::getStatusId() in ApplicationStatuses::setStatus()
		$this->trainingApplicationStorage->insertApplication(
			12,
			123,
			'Name',
			'email@example',
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			TrainingApplicationStatus::SignedUp,
			'michal-spacek',
		);
		$this->assertInsertApplicationParams(
			123,
			'Name',
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			self::STATUS_SIGNED_UP,
		);
	}


	public function testInsertApplicationEmptyStrings(): void
	{
		$this->database->addFetchFieldResult(self::STATUS_SIGNED_UP); // For ApplicationStatuses::getStatusId() in ApplicationStatuses::setStatus()
		$this->trainingApplicationStorage->insertApplication(
			12,
			123,
			'A. Name',
			'email@example',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			null,
			null,
			TrainingApplicationStatus::SignedUp,
			'michal-spacek',
		);
		$this->assertInsertApplicationParams(
			123,
			'A. Name',
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			self::STATUS_SIGNED_UP,
		);
	}


	private function assertInsertApplicationParams(
		int $dateId,
		string $name,
		?string $company,
		?string $street,
		?string $city,
		?string $zip,
		?string $country,
		?string $companyId,
		?string $companyTaxId,
		?string $note,
		?float $price,
		?float $vatRate,
		?float $priceVat,
		?int $discount,
		int $statusId,
	): void {
		$params = $this->database->getParamsArrayForQuery('INSERT INTO training_applications')[0];
		Assert::same($dateId, $params['key_date']);
		Assert::hasNotKey('key_training', $params);
		Assert::same($name, $params['name']);
		if (!is_string($params['email'])) {
			Assert::fail('Email should be a string but is ' . get_debug_type($params['email']));
		} else {
			Assert::match('~\$test\$[^@]+$~', $params['email']);
		}
		Assert::same($company, $params['company']);
		Assert::same($street, $params['street']);
		Assert::same($city, $params['city']);
		Assert::same($zip, $params['zip']);
		Assert::same($country, $params['country']);
		Assert::same($companyId, $params['company_id']);
		Assert::same($companyTaxId, $params['company_tax_id']);
		Assert::same($note, $params['note']);
		Assert::same(self::STATUS_CREATED, $params['key_status']);
		if (!is_string($params['status_time'])) {
			Assert::fail('Status time should be a string but is ' . get_debug_type($params['email']));
		} else {
			Assert::true($this->isVeryRecent($params['status_time']));
		}
		Assert::same('Europe/Prague', $params['status_time_timezone']);
		Assert::same(self::SOURCE_ID, $params['key_source']);
		Assert::same($price, $params['price']);
		Assert::same($vatRate, $params['vat_rate']);
		Assert::same($priceVat, $params['price_vat']);
		Assert::same($discount, $params['discount']);
		if (!is_string($params['access_token'])) {
			Assert::fail('Access token should be a string but is ' . get_debug_type($params['access_token']));
		} else {
			Assert::match('~[0-9a-zA-Z]{14}~', $params['access_token']);
		}

		$setStatusQuery = 'UPDATE training_applications SET ? WHERE id_application = ?';
		$params = $this->database->getParamsArrayForQuery($setStatusQuery)[0];
		Assert::same($statusId, $params['key_status']);
		if (!is_string($params['status_time'])) {
			Assert::fail('Status time should be a string but is ' . get_debug_type($params['email']));
		} else {
			Assert::true($this->isVeryRecent($params['status_time']));
		}
		Assert::same('Europe/Prague', $params['status_time_timezone']);

		Assert::same([self::INSERT_ID], $this->database->getParamsForQuery($setStatusQuery));
	}


	private function buildTrainingDate(): TrainingDate
	{
		return new TrainingDate(
			1,
			'',
			1,
			true,
			false,
			new DateTime(),
			new DateTime(),
			null,
			null,
			true,
			TrainingDateStatus::Confirmed,
			'',
			false,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			false,
			null,
			false,
			null,
			null,
			null,
			null,
		);
	}


	private function isVeryRecent(string $time): bool
	{
		return (new DateTime())->diff(new DateTime($time))->days === 0;
	}

}

TestCaseRunner::run(TrainingApplicationStorageTest::class);
