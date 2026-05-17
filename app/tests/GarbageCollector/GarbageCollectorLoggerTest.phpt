<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\GarbageCollector;

use DateTimeImmutable;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class GarbageCollectorLoggerTest extends TestCase
{

	private const string NOW = '2026-05-17 12:00:00';
	private const string TIMEZONE = 'UTC';


	public function __construct(
		private readonly Database $database,
		private readonly DateTimeMachineFactory $dateTimeFactory,
	) {
		$this->dateTimeFactory->setDateTime(new DateTimeImmutable(self::NOW . ' ' . self::TIMEZONE));
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testLogWritesOkRow(): void
	{
		$logger = new GarbageCollectorLogger($this->database, $this->dateTimeFactory);
		$logger->log(GarbageCollectorType::Sessions, GarbageCollectorReturnCode::Ok, 42, null);
		$expected = [
			'gc_type' => GarbageCollectorType::Sessions->value,
			'gc_time' => self::NOW,
			'gc_time_timezone' => self::TIMEZONE,
			'deleted' => 42,
			'return_code' => GarbageCollectorReturnCode::Ok->value,
			'message' => null,
		];
		Assert::same([$expected, $expected], $this->database->getParamsArrayForQuery('INSERT INTO gc_log'));
	}


	public function testLogWritesFailureRowWithMessage(): void
	{
		$logger = new GarbageCollectorLogger($this->database, $this->dateTimeFactory);
		$logger->log(GarbageCollectorType::AuthTokens, GarbageCollectorReturnCode::Failure, null, 'something broke');
		$expected = [
			'gc_type' => GarbageCollectorType::AuthTokens->value,
			'gc_time' => self::NOW,
			'gc_time_timezone' => self::TIMEZONE,
			'deleted' => null,
			'return_code' => GarbageCollectorReturnCode::Failure->value,
			'message' => 'something broke',
		];
		Assert::same([$expected, $expected], $this->database->getParamsArrayForQuery('INSERT INTO gc_log'));
	}

}

TestCaseRunner::run(GarbageCollectorLoggerTest::class);
