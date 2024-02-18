<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationStatuses;

use DateTime;
use DateTimeInterface;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingApplicationStatusHistoryTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly TrainingApplicationStatusHistory $statusHistory,
	) {
	}


	public function testGetStatusHistory(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'id' => 123,
				'statusId' => 2,
				'status' => 'stat2',
				'statusTime' => new DateTime('2023-10-20 10:20:30'),
				'statusTimeTimeZone' => 'Australia/Darwin',
			],
			[
				'id' => 246,
				'statusId' => 4,
				'status' => 'stat4',
				'statusTime' => new DateTime('2023-10-20 10:20:30'),
				'statusTimeTimeZone' => 'Atlantic/Jan_Mayen',
			],
		]);
		$all = $this->statusHistory->getStatusHistory(303);
		Assert::same(123, $all[0]->getId());
		Assert::same(2, $all[0]->getStatusId());
		Assert::same('stat2', $all[0]->getStatus());
		Assert::equal('2023-10-20T17:50:30+09:30', $all[0]->getStatusTime()->format(DateTimeInterface::RFC3339));
		Assert::same(246, $all[1]->getId());
		Assert::same(4, $all[1]->getStatusId());
		Assert::same('stat4', $all[1]->getStatus());
		Assert::equal('2023-10-20T10:20:30+02:00', $all[1]->getStatusTime()->format(DateTimeInterface::RFC3339));
	}

}

TestCaseRunner::run(TrainingApplicationStatusHistoryTest::class);
