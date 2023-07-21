<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use DateTime;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\TrainingDateStatus;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingApplicationStorageTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TrainingApplicationStorage $trainingApplicationStorage,
	) {
	}


	public function testAddInvitation(): void
	{
		$createdStatus = 1;
		$statusTentative = 2;
		$this->database->setFetchPairsResult([ // For Statuses::getInitialStatuses()
			$statusTentative => 'TENTATIVE',
			4 => 'SIGNED_UP',
			13 => 'IMPORTED',
			14 => 'NON_PUBLIC_TRAINING',
		]);
		$this->database->addFetchFieldResult($createdStatus); // For Statuses::getStatusId() in TrainingApplicationStorage::insertApplication()
		$this->database->addFetchFieldResult(303); // For TrainingApplicationSources::getSourceId in TrainingApplicationStorage::insertApplication()
		$this->database->addFetchFieldResult($statusTentative); // For Statuses::getStatusId() in Statuses::setStatus()
		$this->database->setInsertId('1337');
		$this->database->setFetchResult([ // For Statuses::setStatus() in Statuses::updateStatusCallbackReturnId()
			'statusId' => $createdStatus,
			'statusTime' => new DateTime(),
			'statusTimeTimeZone' => 'Europe/Prague',
		]);

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
			'companyTaxId',
			'note',
		);
		Assert::same(1337, $id);

		$params = $this->database->getParamsArrayForQuery('INSERT INTO training_applications')[0];
		Assert::same(1, $params['key_date']);
		Assert::hasNotKey('key_training', $params);
		Assert::same('Name', $params['name']);
		if (!is_string($params['email'])) {
			Assert::fail('Email should be a string but is ' . get_debug_type($params['email']));
		} else {
			Assert::match('~\$test\$[^@]+$~', $params['email']);
		}
		Assert::same('company', $params['company']);
		Assert::same('street', $params['street']);
		Assert::same('city', $params['city']);
		Assert::same('zip', $params['zip']);
		Assert::same('country', $params['country']);
		Assert::same('companyId', $params['company_id']);
		Assert::same('companyTaxId', $params['company_tax_id']);
		Assert::same('note', $params['note']);
		Assert::same($createdStatus, $params['key_status']);
		if (!is_string($params['status_time'])) {
			Assert::fail('Status time should be a string but is ' . get_debug_type($params['email']));
		} else {
			Assert::true($this->isVeryRecent($params['status_time']));
		}
		Assert::same('Europe/Prague', $params['status_time_timezone']);
		Assert::same(303, $params['key_source']);
		Assert::null($params['price']);
		Assert::null($params['vat_rate']);
		Assert::null($params['price_vat']);
		Assert::null($params['discount']);
		if (!is_string($params['access_token'])) {
			Assert::fail('Access token should be a string but is ' . get_debug_type($params['access_token']));
		} else {
			Assert::match('~[0-9a-zA-Z]{14}~', $params['access_token']);
		}

		$setStatusQuery = 'UPDATE training_applications SET ? WHERE id_application = ?';
		$params = $this->database->getParamsArrayForQuery($setStatusQuery)[0];
		Assert::same($statusTentative, $params['key_status']);
		if (!is_string($params['status_time'])) {
			Assert::fail('Status time should be a string but is ' . get_debug_type($params['email']));
		} else {
			Assert::true($this->isVeryRecent($params['status_time']));
		}
		Assert::same('Europe/Prague', $params['status_time_timezone']);

		Assert::same([1337], $this->database->getParamsForQuery($setStatusQuery));
	}


	public function testInsertApplication(): void
	{
		$this->database->setFetchPairsResult([ // For Statuses::getInitialStatuses()
			2 => 'TENTATIVE',
			4 => 'SIGNED_UP',
			13 => 'IMPORTED',
			14 => 'NON_PUBLIC_TRAINING',
		]);

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
				'ATTENDED',
				'michal-spacek',
			);
		}, RuntimeException::class, "Invalid initial status ATTENDED");
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

$runner->run(TrainingApplicationStorageTest::class);
