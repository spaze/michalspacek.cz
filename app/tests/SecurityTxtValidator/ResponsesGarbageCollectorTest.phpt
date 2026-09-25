<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use DateTimeImmutable;
use MichalSpacekCz\GarbageCollector\GarbageCollectorLogger;
use MichalSpacekCz\GarbageCollector\GarbageCollectorReturnCode;
use MichalSpacekCz\GarbageCollector\GarbageCollectorType;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Database\ResultSet;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactoryUtc;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class ResponsesGarbageCollectorTest extends TestCase
{

	private const string NOW = '2026-09-25 12:00:00';
	private const string TIMEZONE = 'UTC';
	private const string DELETE_OLD_QUERY = 'DELETE FROM responses WHERE fetch_time < ?';


	public function __construct(
		private readonly Database $database,
		private readonly DateTimeMachineFactoryUtc $dateTimeFactoryUtc,
		private readonly DateTimeMachineFactory $dateTimeFactory,
	) {
		$now = new DateTimeImmutable(self::NOW . ' ' . self::TIMEZONE);
		$this->dateTimeFactoryUtc->setDateTime($now);
		$this->dateTimeFactory->setDateTime($now);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testCleanDeletesReplacedRowsAndRowsOlderThanTheLimitAndLogsHowMany(): void
	{
		$this->database->setResultSet(new ResultSet(7));
		Assert::same(GarbageCollectorReturnCode::Ok, $this->createGarbageCollector('1 day')->clean());
		Assert::same(['2026-09-24 12:00:00'], $this->database->getParamsForQuery(self::DELETE_OLD_QUERY));
		$expectedLog = [
			'gc_type' => GarbageCollectorType::SecurityTxtValidatorResponses->value,
			'gc_time' => self::NOW,
			'gc_time_timezone' => self::TIMEZONE,
			'deleted' => 14, // the double answers 7 to both statements, the replaced rows and the old ones
			'return_code' => GarbageCollectorReturnCode::Ok->value,
			'message' => null,
		];
		Assert::same([$expectedLog, $expectedLog], $this->database->getParamsArrayForQuery('INSERT INTO gc_log'));
	}


	public function testCleanLogsAFailedDelete(): void
	{
		$this->database->willThrowOnce(new RuntimeException('Table has left the building'));
		Assert::same(GarbageCollectorReturnCode::Failure, $this->createGarbageCollector('1 day')->clean());
		$expectedLog = [
			'gc_type' => GarbageCollectorType::SecurityTxtValidatorResponses->value,
			'gc_time' => self::NOW,
			'gc_time_timezone' => self::TIMEZONE,
			'deleted' => null,
			'return_code' => GarbageCollectorReturnCode::Failure->value,
			'message' => 'Table has left the building',
		];
		Assert::same([$expectedLog, $expectedLog], $this->database->getParamsArrayForQuery('INSERT INTO gc_log'));
	}


	private function createGarbageCollector(string $deleteAfter): ResponsesGarbageCollector
	{
		return new ResponsesGarbageCollector(
			$this->database,
			$this->dateTimeFactoryUtc,
			new GarbageCollectorLogger($this->database, $this->dateTimeFactory),
			$deleteAfter,
		);
	}

}

TestCaseRunner::run(ResponsesGarbageCollectorTest::class);
