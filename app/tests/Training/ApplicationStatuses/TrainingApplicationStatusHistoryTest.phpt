<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationStatuses;

use DateTime;
use DateTimeInterface;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingApplicationStatusHistoryTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TrainingApplicationStatusHistory $statusHistory,
		private readonly NullLogger $logger,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->logger->reset();
	}


	public function testGetStatusHistory(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'id' => 123,
				'statusId' => 2,
				'status' => TrainingApplicationStatus::SignedUp->value,
				'statusTime' => new DateTime('2023-10-20 10:20:30'),
				'statusTimeTimeZone' => 'Australia/Darwin',
			],
			[
				'id' => 246,
				'statusId' => 4,
				'status' => TrainingApplicationStatus::Spam->value,
				'statusTime' => new DateTime('2023-10-20 10:20:30'),
				'statusTimeTimeZone' => 'Atlantic/Jan_Mayen',
			],
		]);
		$all = $this->statusHistory->getStatusHistory(303);
		Assert::same(123, $all[0]->getId());
		Assert::same(2, $all[0]->getStatusId());
		Assert::same(TrainingApplicationStatus::SignedUp, $all[0]->getStatus());
		Assert::equal('2023-10-20T17:50:30+09:30', $all[0]->getStatusTime()->format(DateTimeInterface::RFC3339));
		Assert::same(246, $all[1]->getId());
		Assert::same(4, $all[1]->getStatusId());
		Assert::same(TrainingApplicationStatus::Spam, $all[1]->getStatus());
		Assert::equal('2023-10-20T10:20:30+02:00', $all[1]->getStatusTime()->format(DateTimeInterface::RFC3339));
	}


	public function testDeleteAllHistoryRecordsMultiple(): void
	{
		$this->statusHistory->deleteAllHistoryRecordsMultiple([1, 2]);
		Assert::same([[1, 2]], $this->database->getParamsArrayForQuery('DELETE FROM training_application_status_history WHERE key_application IN (?)'));
		Assert::same(['Deleting all status history records for applications: 1 2'], $this->logger->getLogged());
	}

}

TestCaseRunner::run(TrainingApplicationStatusHistoryTest::class);
